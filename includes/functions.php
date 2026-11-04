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

/* ---------- Error resilience ---------- */
/* Show a friendly page instead of a raw PHP/SQL crash. Real details go to the log. */
function cinebook_error_page($message = "Something went wrong on our end. Please try again in a moment.") {
    if (!headers_sent()) http_response_code(500);
    $msg = htmlspecialchars($message);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Something went wrong | CineBook</title>'
       . '<link rel="stylesheet" href="css/style.css"></head><body>'
       . '<main class="page-wrap"><div class="form-panel" style="max-width:480px;text-align:center;">'
       . '<h2>Oops!</h2>'
       . '<p class="form-sub">' . $msg . '</p>'
       . '<a href="index.php" class="btn btn-block">Back to Home</a>'
       . '</div></main></body></html>';
    exit;
}

/* Any uncaught exception (e.g. a database error) becomes a friendly page, not a stack trace. */
set_exception_handler(function ($e) {
    error_log("CineBook uncaught: " . $e->getMessage());
    cinebook_error_page();
});

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
        && is_string($_POST['csrf_token'])
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
    if (!is_string($target)) return $default;
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
/** HTML-escape a text field from POST without throwing when malicious arrays are submitted. */
function posted_value($key, $default = '') {
    return htmlspecialchars(is_string($_POST[$key] ?? null) ? $_POST[$key] : $default, ENT_QUOTES, 'UTF-8');
}

/* Inline SVG icon (replaces emoji). Returns an <svg> that inherits currentColor. */
function icon($name, $size = 18) {
    $s = (int) $size;
    $head = '<svg class="ico" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" aria-hidden="true"';
    $stroke = $head . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
    switch ($name) {
        case 'search':  return $stroke . '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.6" y2="16.6"/></svg>';
        case 'clock':   return $stroke . '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
        case 'calendar':return $stroke . '<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v3M16 3v3"/></svg>';
        case 'headset': return $stroke . '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M20 19a3 3 0 0 1-3 3h-3"/></svg>';
        case 'mail':    return $stroke . '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 7.5 12 13l8.5-5.5"/></svg>';
        case 'edit':    return $stroke . '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>';
        case 'pin':     return $stroke . '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
        case 'phone':   return $stroke . '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L8 9.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2z"/></svg>';
        case 'film':    return $stroke . '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v16M17 4v16M3 9h4M3 15h4M17 9h4M17 15h4"/></svg>';
        case 'sun':     return $stroke . '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
        case 'moon':    return $stroke . '<path d="M21 12.8A8 8 0 1 1 11.2 3 6 6 0 0 0 21 12.8z"/></svg>';
        case 'play':    return $head . ' fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
        case 'ticket':  return $stroke . '<path d="M4 6h16a1 1 0 0 1 1 1v3a2 2 0 0 0 0 4v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-3a2 2 0 0 0 0-4V7a1 1 0 0 1 1-1z"/><path d="M12 6.8v1.5M12 9.7v1.5M12 12.6v1.5M12 15.5v1.5"/></svg>';
        // Brand marks — solid (filled) so the social row reads as one consistent set.
        case 'instagram': return $head . ' fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2zm4.5 5.6a4.4 4.4 0 1 0 0 8.8 4.4 4.4 0 0 0 0-8.8zm0 1.9a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zm5-2.2a1.15 1.15 0 1 0 0 2.3 1.15 1.15 0 0 0 0-2.3z"/></svg>';
        case 'facebook':  return $head . ' fill="currentColor"><path d="M14 8.2V6.6c0-.7.5-.9.8-.9H16V3.1l-2.2-.1C11.2 3 10.6 5 10.6 6.3v1.9H8.6V11h2v10h3.3V11h2.3l.4-2.8H14z"/></svg>';
        case 'x':         return $head . ' fill="currentColor"><path d="M18.9 2H22l-7.6 8.7L23 22h-6.8l-5.3-6.9L4.8 22H2l8.1-9.3L1.5 2H8.4l4.8 6.3L18.9 2zm-1.2 18h1.9L7.4 4H5.4z"/></svg>';
        case 'youtube':   return $head . ' fill="currentColor"><path d="M23 7.5a3 3 0 0 0-2.1-2.1C19 5 12 5 12 5s-7 0-8.9.4A3 3 0 0 0 1 7.5 31 31 0 0 0 .7 12 31 31 0 0 0 1 16.5a3 3 0 0 0 2.1 2.1C5 19 12 19 12 19s7 0 8.9-.4a3 3 0 0 0 2.1-2.1A31 31 0 0 0 23.3 12 31 31 0 0 0 23 7.5zM10 15.3V8.7l5.7 3.3z"/></svg>';
        default:        return '';
    }
}
