# Implementation Notes & Deployment Guide

This documents what's actually built (vs. the spec in `laravel-supabase-api-spec.md`), and exactly which config values need to be set to run it locally and in production. Read this alongside the spec, not instead of it — this file explains the *how*, the spec explains the *why*.

## 1. Stack

- **Laravel 13**, PHP 8.4+
- **Postgres** (Supabase-managed in production; SQLite in-memory for the test suite)
- **Redis** — cache/sessions/queues driver, plus a second, unprefixed connection dedicated to the two cross-language queues
- **Laravel Sanctum** for API auth (bearer tokens, no OAuth2 server overhead)
- **Laravel Boost** (dev-only) — installed per [laravel.com/for/agents](https://laravel.com/for/agents), the guidance the `laravel/laravel` skeleton ships as `CLAUDE.md`/`AGENTS.md` for AI coding agents. Provides an MCP server (`.mcp.json`) and Claude Code skills (`.claude/skills/`) for schema/route/config introspection, plus app-tailored guidelines in `CLAUDE.md`. Not required to run the app; safe to remove (`composer remove laravel/boost --dev`) if you don't want the MCP server registered.

## 2. Architecture

```
app/
  Enums/                  Backed enums for every status/provider column (no raw strings)
  Models/                 Eloquent models, relationships, casts
  Http/
    Controllers/Api/       Public, Sanctum-authenticated endpoints (Flutter-facing)
    Controllers/Webhooks/  Public ingestion endpoints — each verifies its own sender before doing anything
    Controllers/Internal/  Parser callbacks — gated by EnsureInternalRequest, never public
    Requests/               Form request validation, one per write endpoint
    Resources/              Output shaping — this is where token/secret fields are kept out of responses
    Middleware/EnsureInternalRequest.php
  Services/                Business logic, thin controllers delegate here
    OAuth/                  Provider abstraction (Google/Microsoft), token refresh, watch registration
    Email/                  Forwarding address generation, signature verification, raw email ingestion, Gmail/Outlook message fetchers
    Trips/                  Trip grouping heuristic, stats aggregation, distance calculation
    Parsing/                Applies a parser callback to the database
    TripUpdates/             Applies one trip-updates queue message
    Queue/                  Thin Redis JSON push/blocking-pop wrapper
  Jobs/                    Laravel-internal queue jobs (Gmail/Outlook push processing) — NOT the cross-language queues
  Console/Commands/
    ConsumeTripUpdates.php   The trip-updates queue worker (own process)
    RenewOAuthWatches.php    Scheduled: renews Gmail/Outlook watches before they expire
  Policies/                 TripPolicy, FlightSegmentPolicy — ownership checks
```

Everything crossing into the Python parser (parse-jobs, trip-updates) goes through `App\Services\Queue\RedisJsonQueue`, which talks to Redis via the `queues` connection in `config/database.php` — that connection has an **empty key prefix**, unlike Laravel's default Redis connection (which prefixes every key with the app name). This matters: a plain Python `redis-py` client reading `rpush`/`blpop` off these keys would never see them if they were prefixed.

## 3. Features implemented

| Area | What's there |
|---|---|
| **Auth** | Register/login/logout/refresh via Sanctum. Registration auto-generates a forwarding address so forwarding-only users work without ever connecting an OAuth provider. |
| **OAuth connections** | `GET /auth/oauth/{provider}/connect` returns a URL (not a redirect — Flutter opens it itself); state token in cache ties the callback back to a user without needing a bearer token on that request. Connecting also calls `setUpWatch()`, which registers a Gmail `users.watch` or a Graph mail subscription and stores the connected account's email + subscription id — required to route push notifications back to the right user, since neither provider's payload carries our user id. |
| **OAuth watch renewal** | `oauth:renew-watches` artisan command, scheduled hourly (`routes/console.php`), renews any connection whose `watch_expiration` is within 24h. |
| **Trips & flight segments** | Full CRUD on trips (`/trips`), `PATCH /segments/{id}` for user edits — any successful edit resolves `needs_review`. Ownership enforced via `TripPolicy`/`FlightSegmentPolicy` (403, not 404, on cross-user access — verified in tests). |
| **Webhooks** | `inbound-email` (HMAC-SHA256 over the raw body), `gmail-push` (a token on the subscription's own URL, matching Google's push-endpoint guidance), `outlook-notifications` (validation handshake + `clientState`). Each does the minimum synchronously and dispatches a `Jobs\Process*` job for the actual provider API calls, so the webhook responds fast. |
| **Internal parser callback** | `PATCH /internal/emails/{id}/parsed`. Idempotent — a retried callback on an already-processed email returns the existing segments instead of duplicating them. Implements concrete defaults for two things the spec left open (§11.1, §11.3) rather than leaving them unusable: a confidence threshold (`needs_review = confidence < CONFIDENCE_REVIEW_THRESHOLD`) and a trip-grouping heuristic (same booking reference, or date-range proximity, else a new trip). |
| **trip-updates consumer** | `php artisan queue:work-trip-updates` — blocking Redis pop, applies gate/time/status changes to the matching segment, cascades to `trip.status = cancelled` if every segment in the trip ends up cancelled. |
| **Stats** | `/stats/lifetime`, `/stats/recap?year=`. Miles via Haversine over `airports.lat/lng`; countries/airlines/cabin mix from the segment set. Cancelled segments excluded. |
| **Lounges** | `/lounges?airport=` — table exists, no data source populated (spec §11.4 is still open; this isn't solved, just not blocking). |
| **Forwarding address** | `/me/forwarding-address` — idempotent generator, safe to call repeatedly. |
| **Rate limiting** | `throttle:api` (60/min/user-or-ip) on all public routes, `throttle:webhooks` (120/min/ip) on webhooks, a flat 60/min on `/internal/*`. |

Routes, for reference (`php artisan route:list`):

```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout                    (auth:sanctum)
POST   /api/auth/refresh                   (auth:sanctum)
GET    /api/auth/oauth/{provider}/connect  (auth:sanctum)
GET    /api/auth/oauth/{provider}/callback (public — state-token authenticated)
DELETE /api/auth/oauth/{provider}          (auth:sanctum)
GET    /api/trips | POST /api/trips
GET|PATCH|DELETE /api/trips/{trip}
PATCH  /api/segments/{segment}
GET    /api/emails/pending-review
GET    /api/stats/lifetime | /api/stats/recap
GET    /api/lounges
GET    /api/me/forwarding-address
PATCH  /internal/emails/{email}/parsed     (X-Internal-Api-Key)
POST   /webhooks/inbound-email             (X-Webhook-Signature)
POST   /webhooks/gmail-push                (?token=)
GET|POST /webhooks/outlook-notifications   (validationToken handshake, then clientState)
```

## 4. What's explicitly *not* resolved (matches spec §11)

These are documented in code (`TripGroupingService`, `ParsedEmailProcessor`, the `lounges` migration) rather than hidden — they have working defaults, not stubs, but the defaults are guesses that should be revisited:

- **Raw email retention** — `raw_emails.raw_content` is stored indefinitely; no purge job exists.
- **Trip grouping heuristic** — booking-reference match, then ±1-day date proximity, then a new trip. Fine for a v1, will misgroup edge cases (e.g. two genuinely separate trips a day apart).
- **Lounge data** — schema only, empty table.

---

## 5. Running locally

### Prerequisites

- PHP 8.4+, Composer
- A Postgres database (or skip this for pure feature dev — see "Testing without Postgres/Redis" below)
- Redis (only needed to exercise webhooks/queue consumption end-to-end)

### Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flighttracker
DB_USERNAME=postgres
DB_PASSWORD=your-local-password

REDIS_CLIENT=phpredis      # or predis — both are installed; phpredis needs the ext, predis needs none
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

```sh
php artisan migrate --seed   # seeds reference airports/airlines (database/seeders)
php artisan serve
```

That alone is enough to exercise **auth, trips, segments, stats, lounges, and forwarding-address** — none of those touch OAuth, webhooks, or the internal endpoint.

### Testing without Postgres/Redis at all

The test suite doesn't need either — `phpunit.xml` overrides `DB_CONNECTION=sqlite`/`DB_DATABASE=:memory:` and mocks the one class that touches Redis:

```sh
php artisan test
```

You can do the same thing manually for ad-hoc local testing:

```sh
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan serve
```

(anything that hits `RedisJsonQueue::push` — i.e. the inbound-email webhook, or the internal parsed-email endpoint via `RawEmailIngestor` — will fail without a real Redis unless you seed a `raw_email` row directly and skip the webhook layer, as the test suite's mocks do).

### To exercise the parts that need extra config

| To test... | Set in `.env` | Also needs |
|---|---|---|
| **Internal parser callback** | `INTERNAL_API_KEY=some-local-secret` | Send `X-Internal-Api-Key: some-local-secret` on the request |
| **Inbound-email webhook** | `INBOUND_EMAIL_WEBHOOK_SECRET=some-local-secret` | Send `X-Webhook-Signature: <hex hmac-sha256 of the raw body, using the same secret>` |
| **Gmail push webhook** | `GMAIL_PUSH_VERIFICATION_TOKEN=some-local-token` | POST to `/webhooks/gmail-push?token=some-local-token` with a Pub/Sub-shaped body (`{"message":{"data":"<base64 of {\"emailAddress\":...,\"historyId\":...}>"}}`) — needs a real `oauth_connections` row with a matching `provider_account_email` to actually do anything |
| **Outlook webhook** | `OUTLOOK_WEBHOOK_CLIENT_STATE=some-local-secret` | POST with `value: [{clientState: "...", subscriptionId: "...", resourceData: {id: "..."}}]` — needs a matching `provider_subscription_id` on an `oauth_connections` row |
| **Gmail/Outlook OAuth connect flow** | `GOOGLE_OAUTH_CLIENT_ID`/`SECRET`/`GOOGLE_OAUTH_REDIRECT_URI`, or the `MICROSOFT_OAUTH_*` equivalents | A real OAuth app (see §7 below) — `Http::fake()` is what the test suite uses instead |
| **trip-updates queue** | — (uses `TRIP_UPDATES_QUEUE_KEY`, defaults to `trip-updates`) | A running Redis + `php artisan queue:work-trip-updates` in a second terminal, then `redis-cli rpush trip-updates '{"marketing_airline_iata":"BA",...}'` |
| **Gmail/Outlook push processing jobs** | `QUEUE_CONNECTION=sync` (or run `php artisan queue:work` in a second terminal) | These are normal Laravel jobs (`app/Jobs`), separate from the trip-updates queue |

None of these are required to develop or test the core CRUD/auth/stats surface — they only matter once you're specifically working on ingestion or OAuth.

---

## 6. Production configuration checklist

### Core app

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=                    # php artisan key:generate --show, set once, never regenerate on a running app
APP_URL=https://api.yourdomain.com
```

### Database — Supabase Postgres

```dotenv
DB_CONNECTION=pgsql
DB_HOST=<supabase-host>
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=<supabase-connection-string-user>
DB_PASSWORD=<supabase-password>
```

Use Supabase's **connection pooler** (transaction mode / port 6543) for the web app's normal connection, since Laravel opens a lot of short-lived connections; use the direct connection (port 5432) for running migrations if the pooler doesn't support them cleanly. This app connects as a plain Postgres credential — it does not use Supabase Auth, RLS, or Realtime, so no Supabase-side policy setup is needed beyond creating the database/user (per spec §2).

Run migrations as part of deploy, not on every boot:

```sh
php artisan migrate --force
php artisan db:seed --class=AirportSeeder --force   # once, or replace with a real IATA dataset
php artisan db:seed --class=AirlineSeeder --force
```

### Redis

A managed Redis (e.g. one with TLS) works fine. Set:

```dotenv
REDIS_CLIENT=phpredis
REDIS_HOST=<redis-host>
REDIS_PORT=6379
REDIS_PASSWORD=<redis-password>
```

**Important**: `config/database.php`'s `redis.queues` connection is hardcoded to an empty key prefix (`'prefix' => ''`) so the Python side sees unprefixed keys — this isn't an env var, it's baked into the config file on purpose. Don't "fix" it by adding a prefix in production; that would silently break cross-language delivery. If Python and Laravel end up on different Redis *databases* by convention, coordinate `REDIS_QUEUES_DB` (defaults to the same DB as everything else, `0`) with whoever owns the Python side.

### Internal endpoint

```dotenv
INTERNAL_API_KEY=<long random secret>
```

Generate with e.g. `openssl rand -hex 32`. Give this to the Python service as the value it sends in `X-Internal-Api-Key`. On top of the shared secret, also restrict `/internal/*` at the network/load-balancer level (spec §8) — the app-level check is not meant to be the only layer.

### Webhooks

```dotenv
INBOUND_EMAIL_WEBHOOK_SECRET=<long random secret>
GMAIL_PUSH_VERIFICATION_TOKEN=<long random token>
GMAIL_PUBSUB_TOPIC=projects/<gcp-project>/topics/<topic-name>
OUTLOOK_WEBHOOK_CLIENT_STATE=<long random secret>
OUTLOOK_NOTIFICATION_URL=https://api.yourdomain.com/webhooks/outlook-notifications
```

Provider-side setup this depends on:

- **Inbound email**: whatever forwarding-parse provider you pick (Mailgun/Postmark/SendGrid inbound parse, etc.) needs to be configured to sign its webhook the same way `InboundEmailSignatureVerifier` checks it (`hash_hmac('sha256', $rawBody, $secret)` in an `X-Webhook-Signature` header) — if the provider's own signing scheme differs, adjust that class to match rather than the other way around, since it verifies against what the provider actually sends.
- **Gmail push**: create a Google Cloud Pub/Sub topic, grant `gmail-api-push@system.gserviceaccount.com` publish rights on it, create a push subscription pointing at `https://api.yourdomain.com/webhooks/gmail-push?token=<GMAIL_PUSH_VERIFICATION_TOKEN>`, and set `GMAIL_PUBSUB_TOPIC` to the topic's full resource name. The app registers the mailbox watch itself (on OAuth connect, and via the hourly renewal command) — no separate `users.watch` setup needed outside the app.
- **Outlook notifications**: no separate provider-side setup — the app creates the Graph subscription itself via `MicrosoftOAuthProvider::setUpWatch()`, pointed at `OUTLOOK_NOTIFICATION_URL`. Just make sure that URL is publicly reachable *before* anyone connects an Outlook account, since Graph validates it synchronously at subscription-creation time.

### OAuth apps (Gmail / Outlook mail read access)

```dotenv
GOOGLE_OAUTH_CLIENT_ID=
GOOGLE_OAUTH_CLIENT_SECRET=
GOOGLE_OAUTH_REDIRECT_URI=https://api.yourdomain.com/auth/oauth/gmail/callback

MICROSOFT_OAUTH_CLIENT_ID=
MICROSOFT_OAUTH_CLIENT_SECRET=
MICROSOFT_OAUTH_TENANT_ID=common          # or a specific tenant id if restricting to org accounts
MICROSOFT_OAUTH_REDIRECT_URI=https://api.yourdomain.com/auth/oauth/outlook/callback
```

- **Google**: create an OAuth 2.0 Client ID (Web application) in Google Cloud Console, add the redirect URI above, enable the Gmail API, and add `https://www.googleapis.com/auth/gmail.readonly` + `.../userinfo.email` scopes. The app requests `access_type=offline&prompt=consent` so it always gets a refresh token — if a user has previously granted access without those params (e.g. via a different app), reconnecting after revoking access in their Google account settings is required to get a fresh refresh token.
- **Microsoft**: register an app in Entra ID (Azure AD), add the redirect URI above as a "Web" platform redirect, and grant delegated `Mail.Read`, `offline_access`, `User.Read` permissions.

### App behaviour

```dotenv
FORWARDING_EMAIL_DOMAIN=fwd.yourdomain.com     # must be an MX-configured domain your inbound-email provider owns/receives for
CONFIDENCE_REVIEW_THRESHOLD=0.8                # tune based on observed parser accuracy
PARSE_JOBS_QUEUE_KEY=parse-jobs                # must match whatever key the Python parser listens on
TRIP_UPDATES_QUEUE_KEY=trip-updates            # must match whatever key pushes trip updates
```

### Processes to run (beyond the web server)

1. **Web server** — standard PHP-FPM/nginx or equivalent (Forge, a container platform — spec §10 leaves this open).
2. **Scheduler** — `* * * * * php artisan schedule:run` via cron (drives `oauth:renew-watches`, hourly).
3. **Laravel's own queue worker** — `php artisan queue:work` (processes `app/Jobs/Process*` — Gmail/Outlook push-notification handling). Set `QUEUE_CONNECTION=redis` (or `database`) in production; `sync` is dev-only.
4. **trip-updates worker** — `php artisan queue:work-trip-updates`, its own supervised process, *not* covered by `queue:work` above (it's a custom command against the raw Redis queue, not a Laravel job).

A minimal supervisor config for #3/#4:

```ini
[program:flighttracker-queue]
command=php /var/www/flighttracker-api/artisan queue:work --tries=3
numprocs=2
autostart=true
autorestart=true

[program:flighttracker-trip-updates]
command=php /var/www/flighttracker-api/artisan queue:work-trip-updates
numprocs=1
autostart=true
autorestart=true
```

### Things worth monitoring (spec §9, not automated by this codebase)

- Queue depth on `parse-jobs` and `trip-updates` (Redis `LLEN`) — a stalled consumer on either side is silent otherwise.
- OAuth connections sitting in `status = error` (set when a refresh-token exchange fails in `OAuthConnectionService::ensureFreshToken`) — that's a user whose email ingestion has silently stopped.
- `oauth:renew-watches` failures — logged via `report()`, but nothing currently pages on them.

None of these have alerting wired up in this codebase; they're flagged here because the spec calls them out as a requirement, not because they're solved.
