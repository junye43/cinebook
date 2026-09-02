<?php
$pageTitle = "Home";
require_once 'includes/db_connect.php';
include 'includes/header.php';

$sql = "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime, c.Cinema_Name
        FROM movie m
        LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
        ORDER BY m.Date ASC, m.Showtime ASC
        LIMIT 4";
$result = $conn->query($sql);
?>

<section class="hero">
    <h1>Book Your Next Movie Night</h1>
    <p>Browse showtimes across our cinemas and reserve your seats in a few clicks.</p>
    <a href="movies.php" class="btn">Browse Movies</a>
    <a href="booking.php" class="btn btn-secondary">Book Now</a>
</section>

<h2 class="section-title">Now Showing</h2>
<div class="movie-grid">
<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="movie-card">
            <div class="movie-poster"><?php echo htmlspecialchars($row['Title']); ?></div>
            <div class="movie-info">
                <h3><?php echo htmlspecialchars($row['Title']); ?></h3>
                <div class="movie-meta">
                    <?php echo htmlspecialchars($row['Cinema_Name']); ?> &middot;
                    <?php echo date("d M Y", strtotime($row['Date'])); ?> &middot;
                    <?php echo date("g:i A", strtotime($row['Showtime'])); ?>
                </div>
                <span class="tag"><?php echo htmlspecialchars($row['Genre']); ?></span>
                <span class="tag"><?php echo intval($row['Duration_Min']); ?> min</span>
                <p style="margin-top:12px;">
                    <a href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>" class="btn">View Details</a>
                </p>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No movies scheduled at the moment. Please check back soon.</p>
<?php endif; ?>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
