# Deploying CineBook for free (InfinityFree)

CineBook is PHP + MySQL, so it needs a classic PHP host. **InfinityFree**
(https://infinityfree.com) is free, supports PHP 8 + MySQL + phpMyAdmin, and
is the closest thing to your local XAMPP.

> You must create the account and log in yourself — that needs your own email
> and password. Everything else is below.

---

## 1. Create a free account + site
1. Go to https://infinityfree.com → **Sign Up** (free).
2. Create a new hosting account / website. Pick the free subdomain it offers
   (e.g. `cinebook.infinityfreeapp.com`), or add your own domain later.
3. Wait a few minutes for the account to activate.

## 2. Create the database
1. In the control panel open **MySQL Databases**.
2. Create a database named `cinebook` (the host turns it into something like
   `epiz_12345678_cinebook`).
3. Write down the four values it shows:
   - **MySQL Hostname** (e.g. `sql123.infinityfree.com`)
   - **MySQL Username** (e.g. `epiz_12345678`)
   - **MySQL Database Name** (e.g. `epiz_12345678_cinebook`)
   - the **password** you set

## 3. Import the data
1. From the panel open **phpMyAdmin** for that database.
2. Select the database on the left, click **Import**.
3. Upload **`deploy/database_hosting.sql`** (NOT `database.sql` — the hosting
   one has no `CREATE DATABASE`, which shared hosts don't allow).
4. Click **Go**. You should see all the tables created.

## 4. Point the app at the host database
1. Copy `includes/db_config.sample.php` → `includes/db_config.php`.
2. Fill in the four values from step 2.
3. (This file is gitignored and stays only on the server — never commit it.)

## 5. Upload the files
Use the control panel's **File Manager** (or FTP with the FTP details in the
panel). Upload the **contents of the cinebook folder** into the host's web root
folder (usually called **`htdocs`**), so files land directly in `htdocs/`, e.g.:

```
htdocs/
  index.php
  movies.php
  includes/
  css/
  images/
  api/
  ...
```

Make sure `includes/db_config.php` (step 4) is uploaded too.

> You do NOT need to upload: `deploy/`, `migrations/`, `tests/`, `database.sql`,
> `README.md`, or the React source in `react/` (see note below).

## 6. Open your site
Visit `http://yoursubdomain.infinityfreeapp.com`. Movies should load,
registration/login and booking should work.

---

## Notes & gotchas
- **First load can take 1–2 min** while the free host "wakes up" / finishes DNS.
- **Accounts/bookings reset** only if you re-import the SQL — don't re-import
  once people start using it.
- **Showtimes stay current** because they're generated relative to today's date.
- **Additional Version (React, 15%)**: the PHP JSON endpoints in `api/` work on
  the host as-is. The `react/` folder is the *source* — if it has a prebuilt
  `build`/`dist` you can upload that; otherwise run `npm run build` locally and
  upload the output. For grading, the React demo usually runs locally — check
  your assignment's requirement.
- **For IE4727 submission**, the grader typically imports `database.sql` into
  their own XAMPP and runs on `localhost`. A live link is a nice extra, not a
  replacement for the zip.
