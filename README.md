# Analytics Platform

## Links

- **Reporting app:** https://reporting.alansdomain.xyz
- **Test site (data source):** https://test.alansdomain.xyz
- **Collector endpoint:** https://collector.alansdomain.xyz
- **Repository:** https://github.com/alzhao011/CSEHW5

## What this is

A full-stack web analytics platform built in PHP. A JavaScript collector on `test.alansdomain.xyz` sends behavioral and performance events to a MySQL database. The reporting app lets authenticated users explore that data through categorized reports, charts, and data tables.

## Architecture

PHP 8.3 MVC — front controller (`index.php`) routes all requests, no framework. MariaDB via mysqli with prepared statements throughout. Apache 2 with mod_rewrite.

## Authentication

- Session-based login with `session_regenerate_id()` on authentication
- Passwords hashed with bcrypt (`password_hash` / `password_verify`)
- Usernames matched with `BINARY` SQL comparison — case-sensitive by design
- Sessions expire after 30 minutes of inactivity
- Three roles: `super_admin`, `analyst`, `viewer` — enforced in middleware on every request
- Analysts can be scoped to specific report sections

## Account recovery

Users register with 3 security questions chosen from a predefined list. Answers are lowercased and bcrypt-hashed before storage. The forgot-password flow requires all three correct answers before allowing a password reset. The questions page renders even for usernames that don't exist to prevent enumeration.

## Security

- CSRF tokens on every state-changing form
- Session cookies: `Secure`, `HttpOnly`, `SameSite=Lax`
- Login lockout after 5 failed attempts — tracked by both username and IP, stored in DB so it persists across restarts
- All queries use parameterized prepared statements
- iframe `src` validated to `http`/`https` before being set (prevents `javascript:` injection)

## Performance

- Composite DB index on `(event_type, created_at)` — most report queries filter on both
- Indexes on `session_id` and `created_at` independently
- 60-second file-based query cache for chart aggregations, keyed by query type + date range
- Heatmap coordinates and raw event tables bypass cache (always live)
- Cache serialized as JSON rather than PHP objects

## Collector

A single vanilla JS file (`collector.js`) loaded on every test site page. Tracks: page loads, clicks (with viewport dimensions for heatmap normalization), throttled mousemoves (1/sec), keydowns, and page unload. Performance timing deferred with `setTimeout(0)` so `loadEventEnd` is stamped before being read. Sends via `navigator.sendBeacon` to avoid blocking page unload.

## Use of AI

Used Claude for scaffolding the initial MVC structure and database queries to speed up development. Wrote specific prompts to get targeted outcomes — things like the role/section middleware, CSRF implementation, and the collector event schema — rather than generating large blocks of code blindly. Still required several rounds of debugging and fixing: a mysqli "commands out of sync" bug from calling `get_result()` inside a loop, negative performance values from reading `loadEventEnd` before the browser had stamped it, and heatmap coordinate misalignment caused by rendering the iframe at a scaled-down CSS width instead of the original recording viewport size.
