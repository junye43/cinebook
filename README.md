# CineBook — Cinema Ticket Booking Portal

**IE4727 Web Application Design — Group Project (Theme 5: Cinema)**

CineBook is a cinema ticket booking web portal where customers can browse films
showing across multiple cinema outlets, view showtimes, register an account, pick
seats on an interactive seat map, and receive a booking confirmation. It is built
in two parts as required by the module:

- **Base Version** — HTML, CSS, vanilla JavaScript, PHP and MySQL, with **no
  frameworks and no AJAX**.
- **Additional Version** — a modern **React** single-page movie browser that
  consumes a **JSON API** over **AJAX**, backed by the same MySQL database.

---

## Tech stack

| Layer | Base Version | Additional Version |
|-------|--------------|--------------------|
| Markup / style | HTML5, CSS3 (`css/style.css`) | HTML5, CSS3 (`react/react-app.css`) |
| Scripting | Vanilla JavaScript (`js/script.js`) | React 18 + Hooks (`react/app.jsx`, via CDN + Babel) |
| Server | PHP (procedural, `mysqli` prepared statements) | PHP JSON endpoint (`api/movies.php`) |
| Database | MySQL / MariaDB (`cinebook_db`) | same database |

---

## Requirements

- **XAMPP** (or any Apache + PHP 8 + MySQL/MariaDB stack)
- A modern web browser
- Internet access for the Additional Version only (React is loaded from a CDN)

---

## Setup & how to run

1. **Copy the project** into your web root so it is served at
   `http://localhost/cinebook/`:
   ```
   xampp/htdocs/cinebook/
   ```
2. **Start Apache and MySQL** from the XAMPP control panel.
3. **Import the database:**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Click **Import** → choose **`database.sql`** → **Go**.
   - This creates the `cinebook_db` database with all tables and sample movies.
     *(No database needs to be selected first — the file creates it.)*
4. **Open the site:** `http://localhost/cinebook/index.php`
5. **Register a customer account** on the site to log in and make a booking.
   *(No account is seeded, so bookings start from a clean state.)*

> **Database credentials:** the app uses XAMPP defaults — host `localhost`,
> user `root`, no password. If your MySQL differs, create
> `includes/db_config.php` (see `includes/db_config.sample.php`) to override them;
> no other file needs editing.

### Viewing the Additional (React) Version
From the footer of any page, click **"Explore Movies (React)"**, or open
`http://localhost/cinebook/react/index.html` directly. It fetches live movie data
from `api/movies.php` and offers client-side search, filtering and sorting.

---

## Key features

- Home page with a hero carousel, **Now Showing** and **Coming Soon** sections
- Movie search plus **location (outlet)** and **genre** filters
- Movie details with cinema info, available showtimes and **MP4 trailer** playback
- Customer **registration, login/logout** and **profile / password** management
- **Secure sessions** and **CSRF protection** on every form
- Booking flow: choose movie → outlet (hall is **auto-allocated**) → showtime →
  seats on an **interactive seat map**, with a live booking summary
- **Age / film-rating restriction** (e.g. NC16, M18) enforced at booking
- **Seat-clash and duplicate-booking prevention** (DB constraints + row locking)
- Booking **confirmation / ticket reference** and personal **booking history**
- Theatre / branch information page
- Shared responsive layout with a **collapsible mobile navigation** and a
  **light / dark theme** toggle
- Graceful error pages when the service is unavailable

---

## Project structure

```
cinebook/
├── index.php              Home (carousel, now showing, coming soon)
├── movies.php             All movies + search / location / genre filters
├── movie_details.php      Movie info, showtimes, trailer
├── booking.php            Showtime + seat selection and reservation
├── confirmation.php       Booking confirmation / ticket reference
├── my_bookings.php        Customer booking history
├── profile.php            View / update profile and password
├── register.php           Account registration
├── login.php / logout.php Authentication
├── about.php              Theatre / branch information
├── includes/              Shared PHP: db connection, header, footer,
│                          helper functions, booking validation
├── css/style.css          Base Version styles (responsive, theming)
├── js/script.js           Base Version scripts (carousel, theme, nav, modals)
├── images/                Posters, backdrops, logo, UI assets
├── api/movies.php         JSON API for the React version
├── react/                 Additional Version (React SPA: app.jsx, index.html, css)
├── migrations/            Incremental SQL (seat protection, reseed showtimes)
├── tests/                 Booking-validation and seat-constraint tests
├── deploy/                Hosting notes + hosting-ready SQL import
└── database.sql           Full schema + sample data (import this)
```

---

## Authors

Group project by **Junye** and **Enya** — IE4727 Web Application Design,
Nanyang Technological University.
