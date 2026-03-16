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
Go to https://reporting.alansdomain.xyz/register. Create an account — you'll be asked to pick 3 security questions and answer each one. Answers are not case-sensitive. New accounts get Viewer access by default.

### 2. Test account recovery
Log out and click **Forgot password?** on the login page. Enter the username you just created. Your three security questions appear — answer them correctly to reach the password reset form. Try entering wrong answers first to see the error handling. The page renders even if the username doesn't exist (no enumeration).

### 3. Log in as Super Admin
Use `superadmin` / `basicallyProfessor123`.

**Case-sensitivity:** Try logging in as `SuperAdmin` (capital S and A) — it fails. Usernames use a `BINARY` SQL comparison.

**Lockout:** Log out and enter the wrong password 5 times. The account locks and shows a countdown. Lockout is tracked by both username and IP.

**Session timeout:** Sessions expire after 30 minutes of inactivity and redirect to login with a notice.

### 4. Traffic report
Go to **Reports → Traffic**. You'll see summary cards, a sessions-over-time line chart, a page views bar chart, device breakdown and connection type pie charts, and a raw events table. Use the **date range filter** at the top — all charts and the table update to the selected window.

### 5. Behavioral report
Go to **Reports → Behavioral**. Scroll to the **Click Heatmap**. It shows a live iframe of the actual page rendered at its original viewport size, with a canvas heatmap overlay — red for hotspots, yellow for lighter activity. Use **Prev / Next** to cycle through tracked pages. The legend shows the max click count for the current page. Heatmap data is always live (not cached).

### 6. Performance report
Go to **Reports → Performance**. Summary cards show avg load time, avg TTFB (time to first byte), and avg DOM ready time. The bar chart and table break these down per page.

### 7. Save a report and export it
On any report page, scroll to the **Save this Report** form. Enter a title, write some analyst comments, check **Publish**, and save. Go to **Saved Reports**, find it, and click **View**. From the view page:
- Click **Print / Export PDF** → clean print layout → use browser's Save as PDF
- Click **Save Snapshot** → writes a static HTML file and gives you a public shareable URL

### 8. Analyst role
Log in as `analyst` / `basicallyTAs123`. This account is restricted to specific report sections. Try navigating directly to a restricted section — you'll get a 403. The analyst can view assigned reports, add comments, and save/publish reports, but cannot access user management.

### 9. Viewer role
Log in as `viewer` / `basicallyStudent123`. Viewers land directly on Saved Reports. Try navigating to `/dashboard` or `/reports/traffic` directly — both return 403. Viewers can only see reports that have been published by an analyst.

### 10. User management
Log back in as `superadmin` and go to **Admin → User Management**. Create a user, change a role, assign section access to an analyst, and delete a user. All forms are CSRF-protected — the token is verified server-side on every POST.
