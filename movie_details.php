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

// Find other showtimes of the same title (server-generated related listing)
$stmt2 = $conn->prepare("SELECT Movie_ID, Date, Showtime FROM movie WHERE Title = ? ORDER BY Date, Showtime");
$stmt2->bind_param("s", $movie['Title']);
$stmt2->execute();
$showtimes = $stmt2->get_result();
?>

<div class="movie-detail">
    <div class="movie-poster"><?php echo htmlspecialchars($movie['Title']); ?></div>
    <div>
        <h1><?php echo htmlspecialchars($movie['Title']); ?></h1>
        <p class="movie-meta">
            <?php echo htmlspecialchars($movie['Genre']); ?> &middot;
            <?php echo intval($movie['Duration_Min']); ?> minutes
        </p>
        <p style="margin-top:14px;"><?php echo nl2br(htmlspecialchars($movie['Description'])); ?></p>

        <p style="margin-top:16px;">
            <strong>Cinema:</strong> <?php echo htmlspecialchars($movie['Cinema_Name']); ?>
            (<?php echo htmlspecialchars($movie['Bran_Location']); ?>)<br>
            <strong>Hall / Location:</strong> <?php echo htmlspecialchars($movie['Location']); ?><br>
            <strong>Contact:</strong> <?php echo htmlspecialchars($movie['Cinema_Cont']); ?>
        </p>

        <h3 style="margin-top:20px;">Available Showtimes</h3>
        <div class="showtime-list">
            <?php while ($s = $showtimes->fetch_assoc()): ?>
                <span class="showtime-chip">
                    <?php echo date("d M", strtotime($s['Date'])); ?> &middot; <?php echo date("g:i A", strtotime($s['Showtime'])); ?>
                </span>
            <?php endwhile; ?>
        </div>

        <p style="margin-top:26px;">
            <a href="booking.php?movie_id=<?php echo $movie['Movie_ID']; ?>" class="btn">Book Tickets for This Movie</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
