# CineBook — Demo Presentation Script (IE4727 Theme 5)

A step-by-step order for demoing the application so nothing is missed — every
feature, every validation, and the security points. Base Version first, then the
Additional (React) Version at the end.

---

## 0. Before you start (setup checklist)

- [ ] **XAMPP**: Apache **and** MySQL both running (green).
- [ ] **Database imported**: `cinebook_db` present in phpMyAdmin.
- [ ] **Showtimes are current.** Showtimes currently run **Nov 2 – Nov 8 2026**. If
      you demo after that window, the "Available Showtimes" will be empty — ask me
      to regenerate them first (one command, 30 seconds).
- [ ] **Have two accounts ready** (or register them live during the demo):
  - An **adult** account (DOB making age ≥ 21) → can book anything.
  - A **young** account (DOB making age ~14–15) → used to show the age/rating gate.
- [ ] Open the site at `http://localhost/cinebook/index.php`.
- [ ] Optional: open the browser **DevTools → Network tab** (for the React section).

---

## 1. Home page (`index.php`)  — first impression
- Point out the **hero carousel** of now-showing films (auto-rotating).
- **Popcorn ratings** on each film (your custom icon — matches the logo).
- **Now Showing** and **Coming Soon** sections.
- **Dark / Light theme toggle** (top-right). Click it — note it **remembers** your
  choice on reload (saved in the browser).
- **Header search** box (we'll use it next).

## 2. Movies listing (`movies.php`) — live search + filters
- Show the full grid of movies with certificate badges + duration pills.
- **Live search-as-you-type**: type part of a title (e.g. "mid") → results filter
  **instantly as you type** (client-side JS).
- Mention the **progressive-enhancement fallback**: the search also works with the
  **Search button** via a server-side SQL query, so it still works with JavaScript off.
- Show the **Cinema** filter dropdown.

## 3. Movie details (`movie_details.php`) — two states
**A showing film** (e.g. *Stellar Horizon*):
- Backdrop banner, popcorn rating, **info cards** (Cinema / Hall / Contact with icons).
- **Available Showtimes grouped by day** (date headings + time chips).
- **Watch Trailer** button (if the film has an MP4) → opens the in-page popup.

**A coming-soon film:**
- Note the rating is **hidden** (not rated yet), it shows **"Opens on …"**, and there
  are **no showtimes / no booking** — only "Browse Now Showing".

## 4. Registration + validation (`register.php`)  ⭐ validation showcase
Open Register and **deliberately trigger each validation** (see cheat-sheet in §9).
- Show the **client-side** pop-up blocking a bad submit.
- Then (optional, strongest point) show the **server-side** validation also catches it
  — the two layers are independent ("defence in depth").
- Finish by **registering successfully** → you're logged in and redirected.

## 5. Login / Logout + access control (`login.php`, `logout.php`)
- **Log out**, then click **Book Now** on any film → you're **redirected to Login**
  ("Please log in to continue"). This proves booking is **protected**.
- Show **login validation**: wrong password → "Incorrect email or password."
- Log back in → you're returned **straight to the booking page** you wanted.

## 6. Booking + validation (`booking.php`)  ⭐ the main event
- The film is **pre-selected**; choose a **Showtime** from the grouped dropdown.
- **Seat map**: pick seats — already-booked seats are **greyed out / disabled**.
- **Live Booking Summary** updates (seats, quantity, total) as you click.
- **Trigger the validations** (see §9): no payment, no seats, too many seats,
  and the **age/rating gate**.
- Submit a valid booking → **Confirmation page**.

## 7. Confirmation (`confirmation.php`)
- Show the booking reference and details (film, date/time, seats, total).

## 8. My Bookings (`my_bookings.php`)
- The reservation you just made appears in the logged-in user's booking history.

## 9. Profile (`profile.php`)
- View account details; show you can **edit** them. (Booking uses this info, which is
  why the booking form no longer re-asks for name/email/contact.)

## 10. Theatres / About (`about.php`)
- The branches table and cinema info — rounds out the site.

## 11. Additional Version — React Edition (`react/index.html`)
Reach it from the **footer → "Explore Movies (React)"**.
- Explain: this is the **Additional Version** — a **React single-page app** that reads
  the **same MySQL database** through a **JSON API** (`api/movies.php`) using **AJAX**.
- Demo the instant, no-reload interactions:
  - **Search** as you type.
  - **Status tabs** (All / Now Showing / Coming Soon).
  - **Genre chips**.
  - **Sort** dropdown (Date / Title / Rating / Duration).
- **Nice touch:** open **DevTools → Network**, reload, and show the single
  `movies.php` request returning **JSON** — proving the AJAX + JSON API.
- Note the icons/popcorn match the base site for a consistent look.

---

## 9. VALIDATION CHEAT-SHEET (don't miss any)

### Registration (`register.php`)
| What to enter | Expected block |
|---|---|
| First/last name with digits or blank | "Please enter a valid first/last name." |
| DOB making age **< 12** (or > 120) | "You must be between 12 and 120 years old." |
| DOB in the future / empty | "Please enter a valid date of birth." |
| Contact number with letters / < 7 digits | "Contact number must be 7-15 digits." |
| Email without `@`/domain | "A valid email is required." |
| Password shorter than 8 chars | "Password must be between 8 and 72 characters." |
| Confirm ≠ Password | "Passwords do not match." |
| Register with an **already-used email** | "An account with this email already exists." |

### Login (`login.php`)
| What to do | Expected block |
|---|---|
| Empty email / password | client pop-up: enter valid email + password |
| Wrong password | "Incorrect email or password." |

### Booking (`booking.php`)
| What to do | Expected block |
|---|---|
| Submit with **no payment method** | "Please select a payment method." |
| Submit with **no seats selected** | "Please select at least one seat." |
| Select **> 10 seats** | "You can select a maximum of 10 seats." |
| Book a higher-rated film with a **too-young** account | "This film is rated … — you must be at least N to book it." |
| (Concurrency) Two people grab the **same seat** | "Seat … was just booked. Please select another." |
| (Edge) A showtime that has **just passed** | "This showtime has already passed." |

**Age/rating limits** (for the age-gate demo): PG13 → 13, **NC16 → 16**, **M18 → 18**,
**R21 → 21**. Use the young account against an **NC16 / M18 / R21** film to trigger it.

---

## Security points to mention (markers love these)
- **SQL injection safe** — every query uses **prepared statements** (`bind_param`).
- **Passwords hashed** with `password_hash()` / `password_verify()` — never stored in plain text.
- **CSRF protection** — every form carries a CSRF token, verified on submit.
- **Session hardening** — `session_regenerate_id()` on login/registration.
- **Output escaping** — `htmlspecialchars()` on all user-supplied output (XSS safe).
- **Open-redirect protection** — the post-login redirect is validated to local paths only.
- **Defence in depth** — validation runs on **both** client (fast feedback) and server (authoritative).
- **Booking integrity** — seat booking runs inside a **DB transaction** with a row lock and a
  **unique key** on seats, so the same seat can never be double-booked.

## Compliance recap (Theme 5 rules)
- **Base Version (85%)**: pure HTML/CSS/vanilla JS/PHP/MySQL — **no** frameworks, AJAX,
  JSON, jQuery, iframes, mailto or external links.
- **Additional Version (15%)**: **React + JSON API + AJAX**, confined to `react/` and `api/`.

---

### Suggested timing (~10–12 min)
Home → Movies/search (2) · Details (1) · **Register + validation (2)** · Login/access (1)
· **Booking + validation (3)** · Confirmation/MyBookings/Profile (1.5) · **React (2)** · Security recap (1).
