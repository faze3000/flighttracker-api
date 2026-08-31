# Flight Tracker API

Laravel backend for the Flight Tracker app: owns all writes to Supabase/Postgres, manages OAuth connections to Gmail/Outlook, ingests inbound emails, orchestrates the (separate) parse pipeline, and serves the Flutter app. Full spec in the project's `laravel-supabase-api-spec.md`.

## Architecture

- `app/Models` — Eloquent models. `casts()` methods use PHP backed enums (`app/Enums`) instead of raw strings for every status/enum column.
- `app/Http/Controllers/Api` — the public, Sanctum-authenticated API Flutter talks to.
- `app/Http/Controllers/Webhooks` — public ingestion endpoints (inbound-email forwarding, Gmail Pub/Sub push, Microsoft Graph change notifications). Each verifies its own sender/signature before doing anything.
- `app/Http/Controllers/Internal` — parser callbacks, gated by `EnsureInternalRequest` (shared-secret header), never public.
- `app/Services` — business logic, organized by concern (`OAuth`, `Email`, `Trips`, `TripUpdates`, `Parsing`, `Queue`). Controllers stay thin; this is where grouping/matching/stats logic lives.
- `app/Console/Commands/ConsumeTripUpdates` — the dedicated worker process for the `trip-updates` Redis queue (`php artisan queue:work-trip-updates`). Plain JSON on Redis lists, not Laravel's job system, since the parse-job and trip-updates queues cross into Python.

## Setup

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # seeds reference airports/airlines
```

The app expects a Postgres connection (Supabase, in production) and Redis. See `.env.example` for every configuration value, including:

- `INTERNAL_API_KEY` — shared secret for `/internal/*`.
- `INBOUND_EMAIL_WEBHOOK_SECRET`, `GMAIL_PUSH_VERIFICATION_TOKEN`, `OUTLOOK_WEBHOOK_CLIENT_STATE` — webhook sender verification.
- `GOOGLE_OAUTH_*`, `MICROSOFT_OAUTH_*` — Gmail/Outlook mail-read OAuth apps.
- `CONFIDENCE_REVIEW_THRESHOLD`, `FORWARDING_EMAIL_DOMAIN`, `PARSE_JOBS_QUEUE_KEY`, `TRIP_UPDATES_QUEUE_KEY` — app behaviour.

## Running the trip-updates worker

```sh
php artisan queue:work-trip-updates
```

Run as its own long-lived process (supervisor/systemd), separate from the web server and from any `php artisan queue:work` process handling Laravel's own internal jobs (e.g. the Gmail/Outlook push-notification jobs in `app/Jobs`).

## Tests

```sh
php artisan test
```
