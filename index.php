<?php
$pageTitle = "Home";
require_once 'includes/db_connect.php';
include 'includes/header.php';

// Now Showing
$showing = $conn->query(
    "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
            m.Description, m.Certificate, m.Stars, m.Poster, c.Cinema_Name
     FROM movie m LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
     WHERE m.Status = 'showing'
     ORDER BY m.Stars DESC, m.Date ASC"
);
$showingRows = $showing ? $showing->fetch_all(MYSQLI_ASSOC) : [];
$featured = $showingRows[0] ?? null;
$rail = array_slice($showingRows, 1, 4);

// Coming Soon
$coming = $conn->query(
    "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
            m.Certificate, m.Stars, m.Poster, c.Cinema_Name
     FROM movie m LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
     WHERE m.Status = 'coming'
     ORDER BY m.Date ASC"
);
?>

<?php if ($featured): ?>
<section class="hero">
    <div class="hero-feature<?php echo poster_has_img($featured['Poster']); ?>" data-genre="<?php echo htmlspecialchars($featured['Genre']); ?>"<?php echo poster_style($featured['Poster']); ?>>
        <div class="hero-content">
            <div class="hero-stars"><?php echo starRating($featured['Stars']); ?></div>
            <h1 class="hero-title"><?php echo htmlspecialchars($featured['Title']); ?></h1>
            <p class="hero-sub"><?php echo htmlspecialchars($featured['Genre']); ?> &middot; <?php echo formatDuration($featured['Duration_Min']); ?></p>
            <p class="hero-released">Showing from <?php echo date("d M Y", strtotime($featured['Date'])); ?></p>
            <p class="hero-desc"><?php echo htmlspecialchars(mb_strimwidth($featured['Description'], 0, 160, '…')); ?></p>
            <div class="hero-actions">
                <a href="movie_details.php?id=<?php echo $featured['Movie_ID']; ?>" class="btn btn-ghost">&#9654; View Details</a>
                <a href="booking.php?movie_id=<?php echo $featured['Movie_ID']; ?>" class="btn">&#127903; Book Tickets</a>
            </div>
        </div>
    </div>
    <div class="hero-rail">
        <?php foreach ($rail as $r): ?>
            <a class="rail-item poster<?php echo poster_has_img($r['Poster']); ?>" data-genre="<?php echo htmlspecialchars($r['Genre']); ?>"<?php echo poster_style($r['Poster']); ?>
               href="movie_details.php?id=<?php echo $r['Movie_ID']; ?>">
                <span class="rail-title"><?php echo htmlspecialchars($r['Title']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<div class="section-head">
    <h2>Now Showing</h2>
    <a href="movies.php" class="view-all">View all</a>
</div>
<div class="movie-grid">
<?php if ($showingRows): ?>
    <?php foreach ($showingRows as $row): ?>
        <div class="movie-card">
            <a class="poster<?php echo poster_has_img($row['Poster']); ?>" data-genre="<?php echo htmlspecialchars($row['Genre']); ?>"<?php echo poster_style($row['Poster']); ?>
               href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>">
                <span class="star-pill"><?php echo starRating($row['Stars']); ?></span>
                <span class="dur-pill">&#9201; <?php echo formatDuration($row['Duration_Min']); ?></span>
            </a>
            <div class="movie-card-foot">
                <div>
                    <div class="mc-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                    <span class="mc-cert">CBFC : <?php echo htmlspecialchars($row['Certificate']); ?></span>
                </div>
                <a href="booking.php?movie_id=<?php echo $row['Movie_ID']; ?>" class="btn btn-sm">&#127903; Book</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty-state"><div class="big">&#127909;</div><p>No movies showing right now. Please check back soon.</p></div>
<?php endif; ?>
</div>

<div class="section-head">
    <h2>Coming Soon</h2>
    <a href="movies.php" class="view-all">View all</a>
</div>
<div class="movie-grid">
<?php if ($coming && $coming->num_rows > 0): ?>
    <?php while ($row = $coming->fetch_assoc()): ?>
        <div class="movie-card">
            <a class="poster<?php echo poster_has_img($row['Poster']); ?>" data-genre="<?php echo htmlspecialchars($row['Genre']); ?>"<?php echo poster_style($row['Poster']); ?>
               href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>">
                <span class="star-pill"><?php echo starRating($row['Stars']); ?></span>
                <span class="dur-pill">&#9201; <?php echo formatDuration($row['Duration_Min']); ?></span>
            </a>
            <div class="movie-card-foot">
                <div>
                    <div class="mc-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                    <span class="mc-cert">Opens <?php echo date("d M", strtotime($row['Date'])); ?></span>
                </div>
                <a href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>" class="btn btn-sm btn-ghost">Details</a>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="empty-state"><p>No upcoming movies announced yet.</p></div>
<?php endif; ?>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
