# CineBook — IE4727 Web Application Design Project
### Theme 5: A Web Portal for Booking Cinema Tickets

This document contains the report deliverables for the **Base Version** of CineBook.
Diagrams: [`docs/sitemap.svg`](sitemap.svg) (storyboard). Wireframes are sketched in
Section 5 and can be redrawn in any diagramming tool.

---

## 1. Application Requirements
*(Overall system behaviour and quality — not just features.)*

| Quality | Requirement | How CineBook meets it |
|---|---|---|
| **Usability** | Users can find showtimes and book seats with minimal effort. | Consistent header/nav and search on every page; visual seat picker with a running total; forms pre-filled from the logged-in account; clear inline validation messages; breadcrumb-free, short booking flow. |
| **Responsiveness** | Layout must adapt to desktop, tablet and phone. | Pure CSS Grid/Flexbox with media queries at 1000/820/620 px. The hero, movie grid, and 3-column booking layout reflow to single column; tables scroll horizontally on small screens. |
| **Security** | Protect user data and prevent common attacks. | Prepared statements everywhere (SQL-injection safe); passwords hashed with `password_hash()`; CSRF tokens on all POST forms; `session_regenerate_id()` on login/register (session-fixation defence); output escaped with `htmlspecialchars()` (XSS); booking gated behind login; open-redirect protection on `?redirect=`; identical login error message (no user enumeration). |
| **Scalability** | Handle growth in movies, cinemas and bookings. | Normalised relational schema (manager → branch → cinema → movie; customer → reservation → transaction). Pages are fully data-driven, so adding movies/cinemas needs **only** database rows, no code changes. |
| **Maintainability** | Easy to extend and modify. | Shared includes (`header.php`, `footer.php`, `functions.php`, `db_connect.php`); one external stylesheet using CSS variables (design tokens); reusable helper functions (`starRating`, `formatDuration`, poster/backdrop helpers, CSRF, auth); commented code. |
| **Accessibility** | Usable with assistive technology. | Semantic HTML5 landmarks (`<header>`, `<nav>`, `<main>`, `<footer>`); a `<label>` for every input; `aria-label` on nav, search and each seat; `alt` text on images; keyboard-focusable controls; high contrast (gold on near-black). |

---

## 2. Functional Requirements
*(What the system does.)*

**FR1 – Browse movies.** Home page shows a hero carousel plus "Now Showing" and "Coming Soon" listings sourced from the database.
**FR2 – Search & filter.** Users can search movies by title (SQL `LIKE`) and filter by cinema on the Movies page.
**FR3 – View movie details.** A dynamic details page shows synopsis, rating, certificate, duration, cinema, and showtimes.
**FR4 – Register.** New users create an account (first/last name, age, contact, address, email, password + confirmation) with client- and server-side validation.
**FR5 – Login / Logout.** Registered users authenticate; sessions manage state; logout destroys the session.
**FR6 – Book tickets (auth-gated).** Logged-in users select a showtime, pick seats on a visual seat map (already-booked seats are disabled per showtime), and submit a booking form; the server records a **reservation** and a **transaction**.
**FR7 – Booking confirmation.** A server-generated e-ticket page shows the booking with poster, seats, total and a booking reference.
**FR8 – Manage bookings.** "Your Tickets" lists the user's bookings and allows updating seats (SQL `UPDATE`).
**FR9 – View theatres.** The About/Theatres page lists cinema branches in a table.

---

## 3. Storyboard / Site Map

See **[`docs/sitemap.svg`](sitemap.svg)** for the visual flowchart.

Navigation summary:

```
Home (index.php)
├── Movies (movies.php)  ── search + cinema filter
│      └── Movie Details (movie_details.php)
│               └── Book Tickets (booking.php)   [requires login]
│                        └── Confirmation (confirmation.php)
├── Theatres / About (about.php)                 branches table
├── Login (login.php) ─┐
├── Register (register.php) ─┘→ Authenticated session
│                              ├── Your Tickets (my_bookings.php)  update seats
│                              └── Logout (logout.php)
└── Search box (header) → Movies results
```

**Access control:** any attempt to reach `booking.php` while logged out redirects to Login and, after a successful login/registration, returns the user to the booking page they wanted.

---

## 4. Wireframes (layout blueprints)

See **[`docs/wireframes.svg`](wireframes.svg)** for polished blueprint wireframes of all key pages. ASCII sketches below for quick reference.

**Home**
```
+------------------------------------------------------+
| LOGO      Home Movies Book Theatres Login   [Search] |
+------------------------------------------------------+
|  <  HERO CAROUSEL (backdrop, title, stars,   >       |
|     buttons: View Details / Book Tickets)  o o o o   |
+------------------------------------------------------+
|  Now Showing                              [View all] |
|  [poster][poster][poster][poster]                    |
|  Coming Soon                              [View all] |
|  [poster][poster][poster][poster]                    |
+------------------------------------------------------+
|  FOOTER: feature cards · logo · nav · socials        |
+------------------------------------------------------+
```

**Movies**  (search + filter, card grid)
```
[ Cinema: v ]   [ search box ][Search]
[poster card][poster card][poster card][poster card]
   title/cert/Book …
```

**Movie Details**
```
+---------------- BACKDROP BANNER ---------------------+
★★★★★   Title
[CBFC] · Genre · 2h 8m
+---------------------+  +---------------------------+
| facts / showtimes   |  | About the movie (synopsis)|
| [Book Tickets]      |  |                           |
+---------------------+  +---------------------------+
```

**Book Tickets**  (3 columns)
```
+-----------+   +--------- SCREEN (curve) ---------+   +-----------+
| Your      |   |  A [][][] .. [][][]              |   | Booking   |
| Details   |   |  B [][][] .. [][][]              |   | Summary   |
| name      |   |  ... seat icons ...             |   | movie     |
| email     |   |  legend: Avail/Reserved/Selected|   | seats     |
| contact   |   +----------------------------------+   | qty/total |
| showtime  |                                          | [Confirm] |
| payment   |                                          +-----------+
+-----------+
```

**Confirmation**  (golden e-ticket)
```
  Congratulations!
  +------------------------------------------+
  | [poster]  N tickets                      |
  |  Movie  Date  Time                       |
  |  Venue  Seats  Total  Ref  Payment  Txn  |
  |  - - - - - - perforation - - - - - - -   |
  |  ||||||| barcode |||||||                 |
  +------------------------------------------+
  [Book Another]  [Back to Home]
```

**Login / Register**
```
+----------- banner -----------+
|  Heading                     |
|  [ field ] [ field ]         |
|  [ field ]                   |
|  [ Submit (full width) ]     |
|  link to the other form      |
+------------------------------+
```

---

## 5. Implementation Details

**Stack:** HTML5, CSS3 (one external stylesheet, CSS variables), vanilla JavaScript, PHP (procedural + `mysqli`), MySQL/MariaDB. No frameworks, no AJAX/JSON (Base-Version compliant).

**Project structure**
```
cinebook/
├── index.php, movies.php, movie_details.php, booking.php,
│   confirmation.php, my_bookings.php, about.php,
│   login.php, register.php, logout.php
├── includes/  db_connect.php · functions.php · header.php · footer.php
├── css/style.css          (external stylesheet, 4+ styles)
├── js/script.js           (hero carousel — plain JS)
├── images/posters, images/backdrops, images/ui
└── database.sql           (schema + sample data)
```

**Key techniques**
- **Server-side rendering:** each PHP page queries MySQL and generates HTML (e.g. `confirmation.php`).
- **Database transactions:** `SELECT` (listings/search), `INSERT` (registration, reservation, transaction), `UPDATE` (seat change in `my_bookings.php`).
- **Prepared statements** (`bind_param`) for every query using user input — prevents SQL injection.
- **Sessions & auth:** `functions.php` provides `require_login()`, `is_logged_in()`, `current_user_id()`; booking is gated and returns the user after login.
- **CSRF protection:** `csrf_field()` injects a per-session token; `csrf_verify()` checks it on every POST.
- **Validation:** HTML5 (`required`, `pattern`, `type`), JavaScript (instant feedback), and authoritative PHP checks (email format, phone digits, password length/match, seat format).
- **Interactive seat map (pure JS):** occupied seats are written into JavaScript by PHP (no AJAX); selecting seats updates hidden fields and a live price total.
- **Images:** poster/backdrop file names stored in the DB; helper functions build the image or fall back to a genre-coloured placeholder.

---

## 6. Base-Version Requirements — Compliance Checklist

| Requirement | Status |
|---|---|
| 1 home + 4–10 content pages, each with title, text & images | ✅ Home + 8 content pages, all with images |
| 1 table displaying content | ✅ `about.php`, `my_bookings.php` |
| 1 form (4+ fields) + server-side processing + DB | ✅ `booking.php` (6 fields), `register.php` |
| SQL Select / Insert / Update | ✅ all three present |
| 1 server-side generated page | ✅ all PHP pages; `confirmation.php` |
| Client-side validation (HTML5 + JS) | ✅ |
| Server-side validation (PHP) | ✅ |
| Form on project site (no external form) | ✅ |
| 1 external CSS with 4+ styles | ✅ `css/style.css` |
| No iframe/frames/mailto/jQuery/JSON/AJAX/Bootstrap | ✅ verified |
| No external/social links | ✅ decorative only |

---

## 7. Test Cases

| # | Feature | Input Data | Expected Output | Test Method | Result |
|---|---|---|---|---|---|
| T1 | Registration – valid | fname=Jane, lname=Tan, age=22, phone=91234567, email=jane@test.com, pw=secret1 (x2) | Account created, auto-logged-in, redirected | Manual UI | Pass |
| T2 | Registration – password mismatch | pw=secret1, confirm=secret2 | Error "Passwords do not match"; no insert | Manual UI | Pass |
| T3 | Registration – duplicate email | email already in DB | Error "account with this email already exists" | Manual UI | Pass |
| T4 | Registration – invalid age | age=5 | Error "Age must be between 12 and 120" | Manual UI | Pass |
| T5 | Login – valid | correct email + password | Session created, redirected to intended page | Manual UI | Pass |
| T6 | Login – wrong password | correct email, wrong pw | Error "Incorrect email or password" | Manual UI | Pass |
| T7 | Auth gate | open `booking.php` while logged out | Redirect to login; after login, return to booking | Manual UI | Pass |
| T8 | Booking – missing seats | fill form, select 0 seats | Error "Please select at least one seat" | Manual UI + PHP | Pass |
| T9 | Booking – invalid contact | contact=abc | Error "Contact number must be 7-15 digits" | JS + PHP | Pass |
| T10 | Booking – too many seats | select 11 seats | Blocked at 10 (alert) | JS | Pass |
| T11 | Seat occupancy | choose showtime with booked seats | Booked seats shown "Reserved" and un-clickable | Manual UI | Pass |
| T12 | Booking – success | valid form + 3 seats | Reservation + transaction inserted; redirect to confirmation | Manual UI + DB check | Pass |
| T13 | Confirmation | valid `res_id` | E-ticket shows poster, seats, total, reference | Manual UI | Pass |
| T14 | Update seats | new_seats=B4, B5 | Row updated (SQL UPDATE); "Seats updated" | Manual UI + DB | Pass |
| T15 | Update seats – bad format | new_seats=zzz | Error "Please enter valid seat(s)"; no update | Manual UI | Pass |
| T16 | Search | q=mid | Only "Midnight Protocol" listed | Manual UI | Pass |
| T17 | Cinema filter | select a cinema | Only that cinema's movies listed | Manual UI | Pass |
| T18 | SQL injection attempt | email=`' OR '1'='1` at login | Login fails safely (prepared statement) | Manual + code review | Pass |
| T19 | CSRF | submit POST with wrong/missing token | Rejected "session expired" | Manual (edit token) | Pass |
| T20 | Responsiveness | resize to 375 px width | Layout stacks to one column, no horizontal scroll | Browser devtools | Pass |
| T21 | Page titles | visit each page | Correct `<title>` in the browser tab | Manual UI | Pass |

*(Fill the "Result" column with your own run and add screenshots as evidence in the final report.)*

---

## 8. (Optional) Modern Enhancement — Additional Version
Not included in the current Base Version. A separate **React.js** single-page movie browser
consuming a PHP JSON API over the same MySQL database can be added for the 15% component,
with justification (modern, reactive, component-based UI beyond the traditional stack).
