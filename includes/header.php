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

// Render a 5-star rating (filled + empty) from a 1-5 value
function starRating($stars) {
    $stars = max(0, min(5, (int) $stars));
    return str_repeat("\u{2605}", $stars) . str_repeat("\u{2606}", 5 - $stars);
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
<link rel="stylesheet" href="css/style.css?v=6">
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
        <a href="index.php" class="logo">
            <img src="images/cinebooklogo.png" alt="CineBook Logo">
        </a>
        <nav class="main-nav" aria-label="Main navigation">
            <ul class="nav-links">
                <li><a href="index.php"<?php echo navActive('index.php'); ?>>Home</a></li>
                <li><a href="movies.php"<?php echo navActive('movies.php'); ?>>Movies</a></li>
                <li><a href="booking.php"<?php echo navActive('booking.php'); ?>>Book Tickets</a></li>
                <li><a href="about.php"<?php echo navActive('about.php'); ?>>Theatres</a></li>
                <li><a href="react/index.html" class="nav-react">Explore Movies (React)</a></li>
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
