<?php
$pageTitle = "Movie Details";
require_once 'includes/db_connect.php';

$movieId = isset($_GET['id']) && is_scalar($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;

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
$stmt2 = $conn->prepare("SELECT Movie_ID, Date, Showtime FROM movie
                         WHERE Title = ? AND Status = 'showing'
                           AND TIMESTAMP(Date, Showtime) > ?
                         ORDER BY Date, Showtime");
$now = (new DateTimeImmutable('now', new DateTimeZone('Asia/Singapore')))->format('Y-m-d H:i:s');
$stmt2->bind_param("ss", $movie['Title'], $now);
$stmt2->execute();
$showtimes = $stmt2->get_result();

// Local MP4 trailers: allow only simple .mp4 filenames and real files in assets/trailers/.
// A movie without its MP4 uploaded yet will not display the button.
$trailerFile = trim((string) ($movie['Trailer_File'] ?? ''));
$hasTrailer = (bool) preg_match('/^[A-Za-z0-9_-]+\.mp4$/i', $trailerFile)
    && is_file(__DIR__ . '/assets/trailers/' . $trailerFile);
?>

<div class="detail-banner">
    <img
        src="images/backdrops/<?php echo htmlspecialchars($movie['Backdrop']); ?>"
        alt="<?php echo htmlspecialchars($movie['Title']); ?>"
        class="detail-banner-img">
</div>

<div class="stars-inline"><?php echo starRating($movie['Stars']); ?></div>
<h1 class="detail-title"><?php echo htmlspecialchars($movie['Title']); ?></h1>
<p style="color:var(--gold);font-size:0.9em;">
    <?php echo $movie['Status'] === 'coming' ? 'Opens on ' : 'Showing from '; ?><?php echo date("d M Y", strtotime($movie['Date'])); ?>
</p>

<div class="detail-meta">
    <span class="pill">Rating: <?php echo htmlspecialchars($movie['Certificate']); ?></span>
    <span>&middot;</span>
    <span><?php echo htmlspecialchars($movie['Genre']); ?></span>
    <span>&middot;</span>
    <span><?php echo formatDuration($movie['Duration_Min']); ?></span>
</div>

<?php if ($hasTrailer): ?>
<div class="detail-trailer-actions">
    <button type="button" id="watchTrailerBtn" class="btn btn-ghost trailer-button">
        <span aria-hidden="true">&#9654;</span> Watch Trailer
    </button>
</div>
<?php endif; ?>

<div class="detail-grid">
    <div>
        <p class="detail-facts">
            <strong>Cinema:</strong><?php echo htmlspecialchars($movie['Cinema_Name']); ?>(<?php echo htmlspecialchars($movie['Bran_Location']); ?>)<br>
            <?php if ($movie['Status'] !== 'coming'): ?><strong>Hall / Location:</strong><?php echo htmlspecialchars($movie['Location']); ?><br>
            <?php endif; ?><strong>Contact:</strong><?php echo htmlspecialchars($movie['Cinema_Cont']); ?>
        </p>

<?php if ($movie['Status'] === 'coming'): ?>
        <h3 style="margin-top:22px;font-size:1.15em;">Coming Soon</h3>
        <p class="detail-facts">This film opens on <strong><?php echo date("d M Y", strtotime($movie['Date'])); ?></strong>. Showtimes aren't open for booking yet — check back closer to the release date.</p>
        <p style="margin-top:22px;">
            <a href="movies.php" class="btn btn-ghost">Browse Now Showing</a>
        </p>
<?php else: ?>
        <h3 style="margin-top:22px;font-size:1.15em;">Available Showtimes</h3>
        <div class="showtime-list">
            <?php while ($s = $showtimes->fetch_assoc()): ?>
                <a class="showtime-chip" href="booking.php?movie_id=<?php echo $s['Movie_ID']; ?>">
                    <?php echo date("d M Y", strtotime($s['Date'])); ?> &middot; <?php echo date("g:i A", strtotime($s['Showtime'])); ?>
                </a>
            <?php endwhile; ?>
        </div>

        <?php if ($showtimes->num_rows > 0): ?>
        <p style="margin-top:26px;">
            <a href="booking.php?movie_id=<?php echo $movie['Movie_ID']; ?>" class="btn"><?php echo icon('ticket',15); ?> Book Tickets</a>
        </p>
        <?php else: ?><p class="hint">No future showtimes available. Please check back later.</p><?php endif; ?>
<?php endif; ?>
    </div>

    <div>
        <p class="about-title">About the movie</p>
        <p style="color:var(--text-mid);"><?php echo nl2br(htmlspecialchars($movie['Description'])); ?></p>
    </div>
</div>

<?php if ($hasTrailer): ?>
<!-- Local MP4 trailer popup (video only loads when user clicks Play). -->
<dialog id="trailerDialog" class="trailer-dialog" aria-labelledby="trailerDialogTitle">
    <div class="trailer-dialog-heading">
        <h2 id="trailerDialogTitle">
            <?php echo htmlspecialchars($movie['Title'], ENT_QUOTES, 'UTF-8'); ?> - Trailer
        </h2>
        <button type="button" id="closeTrailerBtn" class="trailer-close" aria-label="Close trailer">
            &times;
        </button>
    </div>
    <div class="trailer-video-frame">
        <video id="trailerVideo" controls playsinline preload="none"
               aria-label="<?php echo htmlspecialchars($movie['Title'], ENT_QUOTES, 'UTF-8'); ?> trailer">
            <source src="assets/trailers/<?php echo rawurlencode($trailerFile); ?>" type="video/mp4">
            Your browser does not support MP4 video playback.
        </video>
    </div>
</dialog>
<?php endif; ?>

<?php include 'includes/footer.php'; $conn->close(); ?>
