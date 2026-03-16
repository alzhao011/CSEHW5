# Analytics Platform

## Links

- **Reporting app:** https://reporting.alansdomain.xyz
- **Test site (data source):** https://test.alansdomain.xyz
- **Collector endpoint:** https://collector.alansdomain.xyz
- **Repository:** https://github.com/alzhao011/CSEHW5

## What this is

A web analytics platform built in PHP. A JavaScript collector running on test.alansdomain.xyz sends behavioral and performance events to a MySQL database. The reporting app lets users log in and look at that data through a set of reports, charts, and tables depending on their role.

## Architecture

Built with PHP 8.3 using an MVC pattern. A single front controller (index.php) handles all routing with no framework. Database is MariaDB accessed through mysqli with prepared statements. Apache 2 handles the server with mod_rewrite for clean URLs.

## Authentication

- Login is session-based and calls session_regenerate_id() on successful auth
- Passwords are hashed with bcrypt
- Usernames are case-sensitive, enforced with a BINARY comparison in SQL so "Admin" and "admin" are treated as different accounts
- Sessions time out after 30 minutes of inactivity
- There are three roles: super_admin, analyst, and viewer. Access is checked in middleware on every request
- Analyst accounts can be limited to specific report sections

## Account Recovery

When registering, users pick 3 security questions and write answers for each. The answers get lowercased and hashed before being stored so casing doesn't matter when answering later. To reset a password, users enter their username, answer all three questions correctly, and then get a 10-minute window to set a new password. The questions page always shows up even if the username doesn't exist so you can't use it to figure out which usernames are registered.

## Security

- Every form that changes data has a CSRF token that gets verified server-side
- Session cookies are set with Secure, HttpOnly, and SameSite=Lax flags
- Login locks out after 5 failed attempts, tracked by both username and IP so rotating usernames doesn't get around it. Lockout state lives in the database so it survives server restarts
- All database queries use parameterized prepared statements
- The iframe URL in the heatmap is checked to make sure it starts with http or https before being set, to block javascript: protocol injection

## Performance

- Composite index on (event_type, created_at) since most report queries filter on both columns
- Additional indexes on session_id and created_at separately
- Chart aggregation queries are cached to disk for 60 seconds, with the cache key including the query type and date range so filtered and unfiltered results don't collide
- Heatmap coordinates and raw event tables skip the cache so they always show fresh data
- Cache files are stored as JSON instead of serialized PHP objects

## Collector

A single vanilla JS file loaded on every test site page. It tracks page loads, clicks (including the viewport size so the heatmap can normalize coordinates across different screen sizes), mousemoves throttled to once per second, keydowns, and page unloads. Performance timing is read inside a setTimeout so the browser has finished stamping loadEventEnd before we read it. Events are sent with navigator.sendBeacon so they don't block navigation.

Sessions are scoped to browser tabs using sessionStorage. Each new tab gets its own session ID and closing the tab ends that session. This means opening the test site in two tabs counts as two sessions, and coming back to the same tab after navigating around still counts as the same session. This matches how most analytics tools define a session and avoids the problem of localStorage where the same ID persists forever across all visits.

## Resetting event data

There is a "Clear all event data" button at the bottom of the User Management page, accessible only to super admins. It wipes the entire events table and flushes the query cache so the reports start fresh. The reason it exists is so a grader can clear out all the click, pageview, and performance data collected during development and testing, then browse the test site themselves and see only their own activity show up in the reports. Without this the heatmap and charts would be full of noise from prior sessions.

## Export

Reports can be exported as PDF using FPDF, a pure PHP PDF library with no external dependencies. The PDF includes the report title, analyst comments, summary statistics, and data tables for each report category. Charts are not included in the PDF since FPDF does not support rendering HTML canvas elements, but all the underlying numbers are present in table form.

The "Save Snapshot" button generates a static HTML file and saves it to the /exports directory with a shareable URL. The snapshot is a point-in-time copy of the report as it looked when the button was clicked.

## No-JS handling

All charts have noscript fallbacks showing the same underlying data as HTML tables. This means the reports are still readable if JavaScript is disabled; the charts just don't render. The heatmap shows a text note since it depends entirely on JavaScript canvas drawing.

## Roadmap

Things that are reasonable next steps but were not built for this submission:

- **Real-time updates** - Right now the reports are cached for 60 seconds and require a page reload. A WebSocket or polling approach could push fresh data to the dashboard without a full reload.
- **Email notifications** - The platform has no outbound email. Adding SMTP support (via PHPMailer or similar) would enable password reset links, report digest emails, and alerts when metrics cross thresholds.
- **More collector events** - The collector currently tracks clicks, mousemoves, keydowns, pageloads, and unloads. Scroll depth, form abandonment, and copy/paste events would round out the behavioral data.
- **Multiple tracked sites** - Right now the collector is tied to one test domain. Adding a site ID to each event would let one reporting instance handle multiple properties.
- **User-facing dashboard customization** - Users can only see the fixed report views. Drag-and-drop dashboards or saved custom queries would make the platform more useful for different analysts.
- **Chart image export** - PDF exports currently include tables only since FPDF cannot render Chart.js charts. Server-side chart rendering using a headless browser or chart-to-image API would close this gap.

## Use of AI

Used Claude to scaffold the initial MVC structure and database queries to speed up development. Wrote specific prompts to get targeted pieces like the role and section middleware, CSRF helpers, and the collector event schema rather than just dumping the whole project in and generating code blindly. Still needed several rounds of debugging: a mysqli "commands out of sync" error from calling get_result() inside a loop, negative performance values from reading loadEventEnd before the browser stamped it, and heatmap dots landing in the wrong spots because the iframe was being scaled down with CSS instead of being rendered at the original viewport size.
