<?php
$pageTitle = "Home";
require_once 'includes/db_connect.php';
include 'includes/header.php';

// Now Showing
$showing = $conn->query(
    "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
            m.Description, m.Certificate, m.Stars, m.Poster, m.Backdrop, c.Cinema_Name
     FROM movie m LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
     WHERE m.Status = 'showing'
     ORDER BY m.Stars DESC, m.Date ASC"
);
$showingRows = $showing ? $showing->fetch_all(MYSQLI_ASSOC) : [];
$featuredMovies = $showingRows;

// Coming Soon
$coming = $conn->query(
    "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
            m.Description, m.Certificate, m.Stars, m.Poster, m.Backdrop, c.Cinema_Name
     FROM movie m LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
     WHERE m.Status = 'coming'
     ORDER BY m.Date ASC"
);
?>

<?php if ($featuredMovies): ?>

<section class="hero-carousel">
<?php foreach ($featuredMovies as $index => $movie): ?>
<div class="hero-slide <?php echo $index == 0 ? 'active' : ''; ?>">
    <div class="hero-feature<?php echo poster_has_img($movie['Poster']); ?>"
         data-genre="<?php echo htmlspecialchars($movie['Genre']); ?>"
         <?php echo backdrop_style($movie['Backdrop']); ?>>
        <div class="hero-content">

            <div class="hero-stars">
                <?php echo starRating($movie['Stars']); ?>
            </div>

            <h1 class="hero-title">
                <?php echo htmlspecialchars($movie['Title']); ?>
            </h1>

            <p class="hero-sub">
                <?php echo htmlspecialchars($movie['Genre']); ?>
                ·
                <?php echo formatDuration($movie['Duration_Min']); ?>
            </p>

            <p class="hero-desc">
                <?php echo htmlspecialchars(
                    mb_strimwidth($movie['Description'],0,160,'...')
                ); ?>
            </p>

            <div class="hero-actions">

                <a href="movie_details.php?id=<?php echo $movie['Movie_ID']; ?>"
                class="btn btn-ghost">
                    ▶ View Details
                </a>
                
                <a href="booking.php?movie_id=<?php echo $movie['Movie_ID']; ?>"
                class="btn">
                    🎟 Book Tickets
                </a>
            </div>
        </div>
    </div>
</div>

<?php endforeach; ?>


<button class="hero-prev">
❮
</button>

<button class="hero-next">
❯
</button>

<div class="hero-dots">

<?php foreach($featuredMovies as $index=>$movie): ?>

<span class="dot <?php echo $index==0?'active':'';?>"></span>

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
