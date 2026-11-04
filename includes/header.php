<?php
// Ensure shared helpers (icon(), auth, CSRF, session, error handler) are available
// even on pages that include the header before loading db_connect.
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);

function navActive($page) {
    global $currentPage;
    return $currentPage === $page ? ' class="active"' : '';
}

// Format a duration in minutes as e.g. "2h 6m"
function formatDuration($min) {
    $min = (int) $min;
    $h = intdiv($min, 60);
    $m = $min % 60;
    return ($h > 0 ? $h . "h " : "") . $m . "m";
}

// Flat popcorn icon: red/white striped box with a golden fluffy top.
// Filled = full colour; empty = greyed out (via CSS .pop.empty).
function popcorn_icon($filled) {
    $cls = 'pop ' . ($filled ? 'full' : 'empty');
    return '<svg class="' . $cls . '" width="16" height="16" viewBox="0 0 36 36" aria-hidden="true">'
        // red box
        . '<path fill="#EA3943" d="M6.5 13h23l-1.9 18.1a1.5 1.5 0 0 1-1.49 1.35H9.89a1.5 1.5 0 0 1-1.49-1.35L6.5 13z"/>'
        // white stripes
        . '<path fill="#fff" d="M9.8 13h3.27l.86 18.45h-2.6zM16.36 13h3.28l-.28 18.45h-2.72zM22.93 13h3.27l-1.4 18.45h-2.6z"/>'
        // golden popcorn top
        . '<g fill="#F8C517">'
        . '<path d="M6.6 13.4C4.6 8.4 8.4 4 11.6 4.6 12.8 1.8 15.4 1 18 1s4.6.8 5.8 3.6C27 4 30.8 8.4 28.8 13.4z"/>'
        . '<circle cx="9.2" cy="12.4" r="3.1"/><circle cx="26.8" cy="12.6" r="3"/>'
        . '<circle cx="12" cy="9.6" r="3.5"/><circle cx="16" cy="6.5" r="3.9"/>'
        . '<circle cx="20.6" cy="6.3" r="3.8"/><circle cx="24.4" cy="9.4" r="3.3"/>'
        . '<circle cx="14.6" cy="9" r="3.1"/><circle cx="18.6" cy="9.6" r="3.3"/>'
        . '<circle cx="22.2" cy="9.4" r="3.1"/>'
        . '</g>'
        // subtle orange kernel folds
        . '<g fill="none" stroke="#E2941B" stroke-width="1.3" stroke-linecap="round">'
        . '<path d="M12.4 9c.7-.5 1.4-.2 1.5.6"/><path d="M18.2 6.4c.8-.4 1.5 0 1.5.9"/>'
        . '<path d="M22.5 9.2c.7-.5 1.5-.2 1.6.6"/>'
        . '</g>'
        . '</svg>';
}

// Render a 5-popcorn rating from a 1-5 value. Returns '' when there is no rating.
function popcornRating($n) {
    $n = max(0, min(5, (int) $n));
    if ($n <= 0) return '';
    $out = '';
    for ($i = 1; $i <= 5; $i++) $out .= popcorn_icon($i <= $n);
    return '<span class="popcorn-rating" title="' . $n . ' / 5" aria-label="Rated ' . $n . ' out of 5">' . $out . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | CineBook" : "CineBook"; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="CineBook — browse movie showtimes across our cinemas and book your seats online.">
<link rel="icon" type="image/png" href="images/cinebooklogo.png">
<link rel="stylesheet" href="css/style.css?v=20">
<script>
/* Apply the saved theme before paint (prevents a flash of the wrong theme) */
(function () {
    try { if (localStorage.getItem('theme') === 'light') document.documentElement.setAttribute('data-theme', 'light'); } catch (e) {}
})();
</script>
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="logo" title="CineBook home" aria-label="CineBook home">
            <img src="images/cinebooklogo.png" alt="CineBook Logo">
        </a>
        <nav class="main-nav" aria-label="Main navigation">
            <ul class="nav-links">
                <li><a href="movies.php"<?php echo navActive('movies.php'); ?>>Movies</a></li>
                <li><a href="about.php"<?php echo navActive('about.php'); ?>>Theatres</a></li>
                <?php if (isset($_SESSION['cust_id'])): ?>
                    <li><a href="my_bookings.php"<?php echo navActive('my_bookings.php'); ?>>Your Tickets</a></li>
                    <li><a href="profile.php"<?php echo navActive('profile.php'); ?>>My Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php"<?php echo navActive('login.php'); ?>>Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <form class="header-search" method="GET" action="movies.php" role="search">
            <span class="ico-wrap"><?php echo icon('search', 16); ?></span>
            <input type="search" name="q" placeholder="Search movies…"
                   value="<?php echo htmlspecialchars(is_string($_GET['q'] ?? null) ? substr($_GET['q'],0,100) : ''); ?>">
        </form>
        <button type="button" id="themeToggle" class="theme-toggle" title="Toggle light / dark mode" aria-label="Toggle light or dark mode">
            <span class="ico-sun"><?php echo icon('sun', 18); ?></span><span class="ico-moon"><?php echo icon('moon', 18); ?></span>
        </button>
    </div>
</header>
<main class="page-wrap">
