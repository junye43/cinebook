# CineBook — React Edition (Additional Version / Modern Enhancement)

The **Additional Version** (15%) of CineBook, built with **React.js**. It is kept
**separate** from the traditional Base Version (the PHP pages in the parent folder),
so the Base Version remains fully functional and independently graded.

## What it does
A **single-page application (SPA)** that browses all movies with:
- **Live search** by title (updates as you type)
- **Status tabs** — All / Now Showing / Coming Soon
- **Genre filter** chips (built automatically from the data)
- **Sorting** by date, title, rating or duration
- Real poster images and a link back to the Base Version's booking/details pages

## How it is built
| Layer | Technology |
|---|---|
| UI | **React 18 + Hooks** (`useState`, `useEffect`, `useMemo`) |
| JSX | **Babel standalone** (in-browser, no build step) |
| Data | **JSON API** — `../api/movies.php` via `fetch()` (AJAX) |
| Database | the **same MySQL `cinebook_db`** used by the base version |

## Why (justification for the report)
- **Beyond the base stack:** the base is server-rendered PHP (full reload per page). React adds a *client-side, component-based, reactive* UI.
- **Better UX:** search / filter / sort happen instantly on the client with no reload.
- **Clean architecture:** rather than duplicating data, the SPA consumes a JSON API over the existing database — a modern front-end/back-end (API) separation while reusing the base data layer.
- **AJAX + JSON** are permitted in the Additional Version (and are used nowhere in the Base Version).

## How to run
1. Start **Apache + MySQL** (XAMPP) and import `database.sql`.
2. Open **http://localhost/cinebook/react/**
   (must be served over `http://localhost/…`, not opened as a `file://` page, because it calls the API).

## Files
- `index.html` — loads React + Babel and mounts the app
- `app.jsx` — components and application logic
- `react-app.css` — styling (matches the base site)
- `../api/movies.php` — the JSON API endpoint (reads MySQL)
