# Flight Tracker — Laravel/Supabase API Spec (Draft v0.2)

## 1. Purpose

The backend that everything else talks to: serves the Flutter app, owns all writes to Supabase/Postgres, manages OAuth connections to Gmail/Outlook, receives inbound triggers (forwarded email, push notifications), orchestrates the parse pipeline, and applies trip updates. Per the parser spec, Laravel is the *only* writer to the database — Python never touches it directly.

## 2. Responsibility Split

| Layer | Owns |
|---|---|
| **Laravel** | User accounts/auth, OAuth token storage + refresh, inbound webhook receivers, queue producer (parse jobs) + consumer (parsed callbacks and `trip-updates`), all DB writes, business logic (grouping, stats, lounge lookup), public API for Flutter |
| **Supabase/Postgres** | Managed database only (confirmed) — Laravel connects via a standard Postgres credential and bypasses Supabase's Auth/RLS/Realtime layers entirely, keeping authorization logic in one place rather than split across Laravel and RLS policies |
| **Python (parser)** | Classification + extraction only, as specced separately — no DB access |
| **Flutter** | UI, talks to Laravel only — not directly to Supabase (consistent with Laravel as sole writer *and* sole reader-gateway, so there's one place enforcing access rules) |

## 3. Data Model

```
users
  id, email, name, password_hash, created_at, updated_at
  (needed — see §11, Laravel Sanctum confirmed over Supabase Auth)

oauth_connections
  id, user_id, provider (gmail|outlook), access_token (encrypted),
  refresh_token (encrypted), token_expires_at, watch_expiration,
  status (active|revoked|error), created_at, updated_at

forwarding_addresses
  id, user_id, address, created_at

raw_emails
  id, user_id, source (gmail|outlook|forwarded), external_message_id,
  status (pending|parsed|irrelevant|failed), raw_content*, received_at, parsed_at
  * nullable — depends on the retention question carried over from the parser spec

trips
  id, user_id, label, start_date, end_date,
  status (upcoming|completed|cancelled), created_at, updated_at

flight_segments
  id, trip_id, user_id, source_email_id, booking_reference,
  marketing_airline_iata, operating_airline_iata, marketing_flight_number,
  departure_airport_iata, departure_time_local, departure_timezone, departure_terminal, departure_gate,
  arrival_airport_iata, arrival_time_local, arrival_timezone, arrival_terminal, arrival_gate,
  cabin_class, seat, aircraft_type, status (confirmed|changed|cancelled),
  price_amount, price_currency, confidence_score, needs_review (bool),
  created_at, updated_at

airports (reference)   — iata_code, name, city, country, timezone, lat, lng
airlines (reference)   — iata_code, name, alliance
lounges (reference)    — flagged separately, see §7
```

## 4. API Surface

**Public (Flutter-facing)**
- `POST /auth/register`, `/auth/login`, `/auth/logout`, `/auth/refresh` *(contingent on §11 auth decision)*
- `GET /auth/oauth/{provider}/connect` → redirect to Gmail/Outlook consent
- `GET /auth/oauth/{provider}/callback` → handle redirect, store tokens
- `DELETE /auth/oauth/{provider}` → disconnect
- `GET /trips`, `GET /trips/{id}`, `POST /trips` (manual entry), `PATCH /trips/{id}`, `DELETE /trips/{id}`
- `PATCH /segments/{id}` → user edits/confirms a segment, including resolving a `needs_review` flag
- `GET /emails/pending-review` → low-confidence extractions awaiting confirmation
- `GET /stats/lifetime`, `GET /stats/recap`
- `GET /lounges?airport={iata}` → depends on §7
- `GET /me/forwarding-address`

**Internal (parser callbacks — not public)**
- `PATCH /internal/emails/{id}/parsed` → Python posts extraction result + confidence for new bookings
- `trip-updates` queue consumer → a dedicated Laravel queue worker (own process, e.g. `php artisan queue:work trip-updates`), not an HTTP route. Keeps matching/mutation logic in Laravel's existing Eloquent models while still running independently of the web server.

**Webhooks (ingestion triggers)**
- `POST /webhooks/inbound-email` → forwarding provider receiver, verifies sender/signature, stores raw email, enqueues parse job
- `POST /webhooks/gmail-push` → Gmail Pub/Sub push receiver
- `POST /webhooks/outlook-notifications` → Microsoft Graph webhook receiver

## 5. Queue & Cross-Service Integration

Laravel's default queue driver serializes PHP job objects, which Python can't read. For the two queues that cross the language boundary — the parse-job queue and `trip-updates` — use **plain JSON payloads over Redis** (or SQS/RabbitMQ if you'd rather), pushed/consumed with raw client calls (`Redis::rpush()` on the Laravel side, a Python Redis client on the parser side) rather than Laravel's `Job::dispatch()`. Laravel can still use its normal queue system for anything Laravel-only (e.g. notification emails), just not these two.

## 6. Business Logic Ownership

- **Trip grouping** (separate-PNR trips → one trip): still open, carried from the parser spec (§15 there) — this is where it'll actually be implemented once decided.
- **Confidence/review flow**: also still open from the parser spec — `needs_review` on `flight_segments` and the `/emails/pending-review` endpoint above are built assuming a review queue exists; confirm that's the right shape.
- **Lifetime stats / recap**: miles flown, countries, airlines, cabin mix — computed from `flight_segments` + `airports` reference data.
- **Lounge access lookup**: flagged as a gap — no data source has been discussed yet (this isn't something Laravel can compute from flight data alone; it needs an actual lounge database, e.g. Priority Pass-style data or something you already have in mind). Worth treating as its own follow-up rather than folding into this spec.

## 7. Auth Model

**Confirmed: Laravel Sanctum** for app login/session tokens — the right fit for a first-party mobile client holding a bearer token against your own API, without the OAuth2-server overhead Passport brings for a use case that doesn't need it. Socialite can layer on top later for "Sign in with Google" as a login option, if wanted.

Worth keeping two concerns distinct regardless:
- **App login** (Sanctum) — how a user gets into the app at all, including forwarding-only users who never connect Gmail/Outlook.
- **OAuth connections** — the Gmail/Outlook grants for reading email, independent of how the user logged in. A Google login could piggyback the same consent screen as the Gmail read-access grant for convenience, but Outlook and forwarding-only users still need Sanctum-based login to work without any Google account at all.

## 8. Security Notes

- Internal endpoints (`/internal/*`) must not be reachable from the public internet unauthenticated — shared-secret header or network-level restriction, checked by Laravel middleware.
- Webhook receivers verify sender/signature (already noted in the parser spec for inbound email; applies equally to Gmail/Outlook webhook receivers where those providers support signed payloads).
- OAuth tokens encrypted at rest (Laravel's built-in encrypted casts handle this cleanly).

## 9. Non-Functional

- Rate limiting on public API routes (standard Laravel throttle middleware).
- Data deletion: a user closing their account should have a clear path to full data erasure, given the PII involved (this isn't legal advice, just an engineering note worth designing for from the start rather than retrofitting).
- Basic monitoring/alerting on queue depth and OAuth token refresh failures, since a silently-expired refresh token means an account stops ingesting emails with no obvious symptom until the user notices.

## 10. Deployment

Not yet decided — Supabase itself is managed, but Laravel's hosting (Forge + a VPS, a container platform, etc.) is open. Not blocking for finishing this spec.

## 11. Open Questions

**Resolved (v0.2):** Supabase scope (pure managed Postgres, Laravel sole gateway, no Auth/RLS/Realtime), app login mechanism (Laravel Sanctum).

**Carried over from the parser spec (still unresolved, now also live here):**
1. Review flow — low-confidence extractions saved as "unconfirmed" pending edit, or something else?
2. Raw email retention — keep `raw_content` after parsing, or discard once structured? (Directly affects the `raw_emails` table above.)
3. Trip grouping — auto-group separately-booked trips by date/location proximity, or one-trip-per-booking with manual merge?

**New to this layer:**
4. Lounge access data — is there a data source you already have in mind, or does this need its own research pass before it's spec-able at all?
5. Broker choice for the cross-language queues — Redis, SQS, or RabbitMQ, or no preference?
