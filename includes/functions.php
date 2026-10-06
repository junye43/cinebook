<?php
/* ============================================================
   Shared helper functions (loaded on every page via db_connect)
   - Session startup
   - CSRF protection
   - Authentication helpers
   - Safe local redirects
   - Poster image helpers
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- CSRF protection ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}
function csrf_verify() {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/* ---------- Authentication ---------- */
function is_logged_in()   { return isset($_SESSION['cust_id']); }
function current_user_id(){ return $_SESSION['cust_id'] ?? null; }

/* Redirect to login if not signed in, remembering where to return to. */
function require_login($returnTo) {
    if (!is_logged_in()) {
        header('Location: login.php?redirect=' . urlencode($returnTo));
        exit;
    }
}

/* Only allow redirects to a local .php page on this site (prevents open redirects). */
function safe_local_redirect($target, $default = 'index.php') {
    $target = (string) $target;
    if ($target === '' || strpos($target, '://') !== false
        || strpos($target, '//') === 0 || $target[0] === '/') {
        return $default;
    }
    if (!preg_match('#^[A-Za-z0-9_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', $target)) {
        return $default;
    }
    return $target;
}

/* ---------- Poster images ---------- */
/* Returns the poster filename if it exists in images/posters/, else ''. */
function poster_file($poster) {
    $poster = trim((string) $poster);
    if ($poster !== ''
        && preg_match('/^[A-Za-z0-9._\- ]+\.(jpg|jpeg|png|webp|gif)$/i', $poster)
        && file_exists(__DIR__ . '/../images/posters/' . $poster)) {
        return $poster;
    }
    return '';
}
/* Extra CSS class when a real poster image is present. */
function poster_has_img($poster) {
    return poster_file($poster) !== '' ? ' has-img' : '';
}
/* Inline background-image style attribute for a poster (or empty string). */
function poster_style($poster) {
    $f = poster_file($poster);
    if ($f === '') return '';
    return ' style="background-image:url(\'images/posters/' . htmlspecialchars(rawurlencode($f)) . '\')"';
}

function backdrop_style($file)
{
    if(!$file){
        return '';
    }
    $path = __DIR__ . '/../images/backdrops/' . $file;
    if(file_exists($path))
    {
        return ' style="background-image:url(\'images/backdrops/' 
        . htmlspecialchars(rawurlencode($file)) 
        . '\')"';
    }
    return '';
}

/* Age in whole years from a date of birth (Y-m-d), or null if invalid/future. */
function age_from_dob($dob) {
    $dob = trim((string) $dob);
    if ($dob === '') return null;
    $d = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$d || $d->format('Y-m-d') !== $dob) return null;
    $today = new DateTime('today');
    if ($d > $today) return null;
    return (int) $d->diff($today)->y;
}

/* Minimum age required for a film certificate (0 = no age restriction). */
function cert_min_age($cert) {
    $map = ['PG13' => 13, 'NC16' => 16, 'M18' => 18, 'R21' => 21];
    $k = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $cert));
    return $map[$k] ?? 0;
}

/* Coloured film-certificate badge (G / PG / PG13 / NC16 / M18 / R21). */
function cert_badge($cert) {
    $cert = trim((string) $cert);
    if ($cert === '') return '';
    $cls = preg_replace('/[^A-Z0-9]/', '', strtoupper($cert));
    return '<span class="cert-badge cert-' . $cls . '">' . htmlspecialchars($cert) . '</span>';
}