<?php
$pageTitle = "Movies";
require_once 'includes/db_connect.php';
include 'includes/header.php';

$cinemaFilter = isset($_GET['cinema']) ? intval($_GET['cinema']) : 0;
$q = trim($_GET['q'] ?? '');

$cinemas = $conn->query("SELECT Cinema_ID, Cinema_Name FROM cinema ORDER BY Cinema_Name");

// Build a filtered query with a prepared statement (SELECT + search + filter)
$sql = "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
               m.Certificate, m.Stars, m.Status, m.Poster, c.Cinema_Name
        FROM movie m LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
        WHERE 1=1";
$params = [];
$types = "";
if ($q !== '') { $sql .= " AND m.Title LIKE ?"; $params[] = "%$q%"; $types .= "s"; }
if ($cinemaFilter > 0) { $sql .= " AND m.Cinema_ID = ?"; $params[] = $cinemaFilter; $types .= "i"; }
$sql .= " ORDER BY m.Date ASC, m.Showtime ASC";

$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="section-head">
    <h2><?php echo $q !== '' ? 'Results for “' . htmlspecialchars($q) . '”' : 'All Movies &amp; Showtimes'; ?></h2>
</div>

<div class="toolbar">
    <form method="GET" action="movies.php">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>"><?php endif; ?>
        <label for="cinema">Cinema:</label>
        <select name="cinema" id="cinema" onchange="this.form.submit()">
            <option value="0">All Cinemas</option>
            <?php while ($c = $cinemas->fetch_assoc()): ?>
                <option value="<?php echo $c['Cinema_ID']; ?>" <?php echo ($cinemaFilter == $c['Cinema_ID']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['Cinema_Name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
    <form method="GET" action="movies.php">
        <input type="search" name="q" placeholder="Search by title…" value="<?php echo htmlspecialchars($q); ?>">
        <?php if ($cinemaFilter > 0): ?><input type="hidden" name="cinema" value="<?php echo $cinemaFilter; ?>"><?php endif; ?>
        <button type="submit" class="btn btn-sm">Search</button>
    </form>
</div>

<div class="movie-grid">
<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="movie-card">
            <a class="poster<?php echo poster_has_img($row['Poster']); ?>" data-genre="<?php echo htmlspecialchars($row['Genre']); ?>"<?php echo poster_style($row['Poster']); ?>
               href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>">
                <span class="star-pill"><?php echo starRating($row['Stars']); ?></span>
                <span class="dur-pill">&#9201; <?php echo formatDuration($row['Duration_Min']); ?></span>
            </a>
            <div class="movie-card-foot">
                <div>
                    <div class="mc-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                    <span class="mc-cert"><?php echo htmlspecialchars($row['Cinema_Name']); ?> &middot; <?php echo date("d M", strtotime($row['Date'])); ?></span>
                </div>
                <?php if ($row['Status'] === 'coming'): ?>
                    <a href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>" class="btn btn-sm btn-ghost">Details</a>
                <?php else: ?>
                    <a href="booking.php?movie_id=<?php echo $row['Movie_ID']; ?>" class="btn btn-sm">&#127903; Book</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="empty-state"><div class="big">&#128269;</div><p>No movies found<?php echo $q !== '' ? ' for “' . htmlspecialchars($q) . '”' : ''; ?>. <a href="movies.php" style="color:var(--gold);">Clear filters</a>.</p></div>
<?php endif; ?>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
