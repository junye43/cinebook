<?php
$pageTitle = "Movie Details";
require_once 'includes/db_connect.php';

$movieId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT m.*, c.Cinema_Name, c.Cinema_Cont, b.Bran_Location
                         FROM movie m
                         LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
                         LEFT JOIN branch b ON c.Bran_ID = b.Bran_ID
                         WHERE m.Movie_ID = ?");
$stmt->bind_param("i", $movieId);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

include 'includes/header.php';

if (!$movie) {
    echo "<div class='error-msg'>Movie not found.</div>";
    include 'includes/footer.php';
    exit;
}

// Other showtimes of the same title (server-generated related listing)
$stmt2 = $conn->prepare("SELECT Movie_ID, Date, Showtime FROM movie WHERE Title = ? ORDER BY Date, Showtime");
$stmt2->bind_param("s", $movie['Title']);
$stmt2->execute();
$showtimes = $stmt2->get_result();
?>

<div class="detail-banner<?php echo poster_has_img($movie['Poster']); ?>" data-genre="<?php echo htmlspecialchars($movie['Genre']); ?>"<?php echo poster_style($movie['Poster']); ?>>
    <?php if (poster_file($movie['Poster']) === ''): ?>
        <span class="poster-title serif" style="font-size:2em;position:relative;z-index:2;">
            <?php echo htmlspecialchars($movie['Title']); ?>
        </span>
    <?php endif; ?>
</div>

<div class="stars-inline"><?php echo starRating($movie['Stars']); ?></div>
<h1 class="detail-title"><?php echo htmlspecialchars($movie['Title']); ?></h1>
<p style="color:var(--gold);font-size:0.9em;">Showing from <?php echo date("d M Y", strtotime($movie['Date'])); ?></p>

<div class="detail-meta">
    <span class="pill">CBFC : <?php echo htmlspecialchars($movie['Certificate']); ?></span>
    <span>&middot;</span>
    <span><?php echo htmlspecialchars($movie['Genre']); ?></span>
    <span>&middot;</span>
    <span><?php echo formatDuration($movie['Duration_Min']); ?></span>
</div>

<div class="detail-grid">
    <div>
        <p class="detail-facts">
            <strong>Cinema:</strong> <?php echo htmlspecialchars($movie['Cinema_Name']); ?>
            (<?php echo htmlspecialchars($movie['Bran_Location']); ?>)<br>
            <strong>Hall / Location:</strong> <?php echo htmlspecialchars($movie['Location']); ?><br>
            <strong>Contact:</strong> <?php echo htmlspecialchars($movie['Cinema_Cont']); ?>
        </p>

        <h3 style="margin-top:22px;font-size:1.15em;">Available Showtimes</h3>
        <div class="showtime-list">
            <?php while ($s = $showtimes->fetch_assoc()): ?>
                <a class="showtime-chip" href="booking.php?movie_id=<?php echo $s['Movie_ID']; ?>">
                    <?php echo date("d M", strtotime($s['Date'])); ?> &middot; <?php echo date("g:i A", strtotime($s['Showtime'])); ?>
                </a>
            <?php endwhile; ?>
        </div>

        <p style="margin-top:26px;">
            <a href="booking.php?movie_id=<?php echo $movie['Movie_ID']; ?>" class="btn">&#127903; Book Tickets</a>
        </p>
    </div>

    <div>
        <p class="about-title">About the movie</p>
        <p style="color:var(--text-mid);"><?php echo nl2br(htmlspecialchars($movie['Description'])); ?></p>
    </div>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
