<?php
if (session_status() === PHP_SESSION_NONE) session_start();
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
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="logo">Cine<span class="tk">my</span>Book</a>
        <nav class="main-nav" aria-label="Main navigation">
            <ul class="nav-links">
                <li><a href="index.php"<?php echo navActive('index.php'); ?>>Home</a></li>
                <li><a href="movies.php"<?php echo navActive('movies.php'); ?>>Movies</a></li>
                <li><a href="booking.php"<?php echo navActive('booking.php'); ?>>Book Tickets</a></li>
                <li><a href="about.php"<?php echo navActive('about.php'); ?>>Theatres</a></li>
                <?php if (isset($_SESSION['cust_id'])): ?>
                    <li><a href="my_bookings.php"<?php echo navActive('my_bookings.php'); ?>>Your Tickets</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php"<?php echo navActive('login.php'); ?>>Login</a></li>
                    <li><a href="register.php"<?php echo navActive('register.php'); ?>>Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <form class="header-search" method="GET" action="movies.php" role="search">
            <span class="ico">&#128269;</span>
            <input type="search" name="q" placeholder="Search movies…"
                   value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
        </form>
    </div>
</header>
<main class="page-wrap">
