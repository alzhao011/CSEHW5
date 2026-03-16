# Grader Instructions

**Reporting app:** https://reporting.alansdomain.xyz
**Test site:** https://test.alansdomain.xyz

## Credentials

| Role | Username | Password |
|------|----------|----------|
| Super Admin | `superadmin` | `basicallyProfessor123` |
| Analyst | `analyst` | `basicallyTAs123` |
| Viewer | `viewer` | `basicallyStudent123` |

---

## Walkthrough

### 1. Register a new account
Go to https://reporting.alansdomain.xyz/register and create an account. You will be asked to pick 3 security questions and write an answer for each one. Answers are not case-sensitive. New accounts start with Viewer access by default.

### 2. Test account recovery
Log out and click "Forgot password?" on the login page. Enter the username you just created and your three security questions will show up. Try answering one wrong first to see the error. Then answer all three correctly and you will get to the password reset form. You have 10 minutes once verified. Also try entering a username that does not exist and you will notice the page still shows questions so you cannot tell which usernames are real.

### 3. Log in as Super Admin
Use superadmin / basicallyProfessor123.

**Case-sensitivity check:** Try logging in as SuperAdmin with a capital S and A. It will fail because usernames are case-sensitive at the database level.

**Lockout check:** Log out and enter the wrong password 5 times in a row. The account will lock and show how many minutes are left. Lockout tracks both the username and the IP address so changing usernames does not help.

**Session timeout:** Sessions expire after 30 minutes of no activity. When it expires you get sent back to login with a message saying your session timed out.

### 4. Generate fresh data
Before exploring the reports, go to https://test.alansdomain.xyz and browse around for a minute. Click things, visit a few pages, type something in the contact form. This generates the events that show up in the reports. Come back to the reporting app when done.

To generate multiple sessions (so the Traffic report shows more than one), browse the test site, then wait 30 minutes and browse again — or clear your localStorage between visits. Sessions expire after 30 minutes of inactivity, the same way Google Analytics defines a session.

### 5. Traffic report
Go to Reports, then Traffic. You will see total pageviews, sessions, unique pages, and bounce rate at the top. Below that are a line chart of sessions over time, an activity heatmap by hour and day of week, a bar chart of pageviews by URL, and a doughnut chart for device types. There is also a raw page views table at the bottom. Try using the date range filter at the top and everything on the page will update to that window.

### 6. Behavioral report
Go to Reports, then Behavioral. Scroll down to the Click Heatmap section. You will see a placeholder with a **Load Page Preview** button — click it to load a live version of the tracked page inside an iframe. The canvas overlay on top shows where users clicked: red means a lot of clicks, yellow means fewer. Use the Prev and Next buttons to switch between tracked pages. The preview is not loaded automatically to avoid generating phantom tracking events. The click dot data is always fresh and is not cached.

### 7. Performance report
Go to Reports, then Performance. The summary cards at the top show average load time, average TTFB (time to first byte), and average DOM ready time across all samples. The bar chart and table below break those numbers down by page.

### 8. Save a report and try the export
On any report page scroll down to the Save this Report section. Give it a title, write some comments, check Publish so viewers can see it, and save it. Then go to Saved Reports and click View on the one you just saved. From there you can click **Download PDF** to download a server-generated PDF file (built using FPDF, a PHP library). It includes the title, analyst comments, summary stats, a chart image, and data tables. You can also click Save Snapshot to write a static HTML file with a shareable URL.

### 9. Analyst role and section permissions
Log out and log in as analyst / basicallyTAs123. You will see the analyst's sections listed in the navbar next to the username, for example (analyst:traffic, behavioral). Try navigating to a section the analyst does not have access to by typing the URL directly and you will get a 403.

To change what sections an analyst can access, log back in as superadmin and go to User Management. Click Edit next to the analyst account. Choose the role "analyst" from the dropdown and check or uncheck whichever sections you want them to have. If you save with all checkboxes unchecked the analyst gets access to all sections, which shows up as (analyst:all) in the navbar.

### 10. Viewer role
Log out and log in as viewer / basicallyStudent123. Viewers go straight to Saved Reports after login. Try typing /dashboard or /reports/traffic into the URL bar and you will get a 403 both times. Viewers can only see reports that have been published.

### 11. Reset data and verify
Log in as superadmin and go to Admin, then User Management. Scroll to the bottom and click "Clear all event data". This wipes everything collected during development so the reports start fresh. Then go back to test.alansdomain.xyz, browse around, and come back to the reports to confirm only your own activity is showing up.

### 12. User management
Log back in as superadmin and go to Admin, then User Management. From here you can create a new user, change someone's role, limit an analyst to specific sections, or delete a user. Every form on this page is protected with a CSRF token.

### 13. PDF export
Go to Saved Reports and open any saved report. Click **Download PDF**. The browser captures the chart canvas as a PNG, submits it with the request, and the server generates a PDF using FPDF (a PHP PDF library) and sends it as a download. It includes the report title, analyst comments, summary stats, a chart image, and data tables. This is a server-generated file, not a browser print dialog.

### 14. No-JS fallback
Disable JavaScript in your browser (Chrome: DevTools, Settings, Debugger, Disable JavaScript) and reload any report page. The charts won't render but the data tables and summary cards are still there. The heatmap shows a text note explaining JavaScript is required for the interactive view.

---

## Known Limitations and Design Choices

**PDF charts** - The PDF includes one chart image per report (the main chart from the saved report view). The chart is captured client-side as a PNG from the Chart.js canvas and submitted with the download request. The server embeds it using FPDF's Image() method.

**Heatmap without JS** - The heatmap is entirely dependent on JavaScript canvas drawing. There is no meaningful static fallback for it other than pointing to the raw clicks table below it, which is what we do.

**Single collector domain** - The collector is set up for test.alansdomain.xyz only. The Accept-Origin header on the collector API is locked to that domain. Adding more sites would require either making the origin configurable or deploying separate collector instances.

**Pre-existing accounts have no security questions** - The superadmin and analyst accounts were created directly in the database before the security question system was built. If you try to use "Forgot password" on them you get a message saying to contact the website manager. Only accounts registered through the signup form have security questions set.

**Session definition** - Sessions expire after 30 minutes of inactivity using localStorage. Each event in the collector resets the 30-minute clock. If a user comes back after more than 30 minutes, their next event starts a new session. This matches how Google Analytics defines a session.

**No fallback if all three security questions are forgotten** - Account recovery depends entirely on the user remembering their three security question answers. If they forget all of them there is no secondary path like an email verification link or admin-assisted reset. A super admin can manually reset someone's password from the database, but there is no self-service option. The reason the platform has no email support is that setting up outbound SMTP requires a third-party service (SendGrid, Mailgun, etc.) which was out of scope. Adding email as a second recovery factor is listed in the roadmap.
