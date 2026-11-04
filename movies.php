<?php
$pageTitle = "Movies";
require_once 'includes/db_connect.php';
include 'includes/header.php';

$cinemaFilter = isset($_GET['cinema']) && is_scalar($_GET['cinema']) && ctype_digit((string)$_GET['cinema']) ? (int)$_GET['cinema'] : 0;
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$q = substr($q, 0, 100);

$cinemas = $conn->query("SELECT Cinema_ID, Cinema_Name FROM cinema ORDER BY Cinema_Name");

// Build a filtered query with a prepared statement (SELECT + search + filter)
$sql = "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
               m.Certificate, m.Stars, m.Status, m.Poster, c.Cinema_Name
        FROM movie m LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
        WHERE m.Movie_ID IN (SELECT MIN(Movie_ID) FROM movie GROUP BY Title)";
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
        <div class="movie-card" data-title="<?php echo htmlspecialchars($row['Title']); ?>">
            <a class="poster<?php echo poster_has_img($row['Poster']); ?>" data-genre="<?php echo htmlspecialchars($row['Genre']); ?>"<?php echo poster_style($row['Poster']); ?>
               href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>">
                <?php echo cert_badge($row['Certificate']); ?>
                <span class="dur-pill"><?php echo icon('clock',13); ?><?php echo formatDuration($row['Duration_Min']); ?></span>
            </a>
            <div class="movie-card-foot">
                <a class="mc-title" href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>"><?php echo htmlspecialchars($row['Title']); ?></a>
                <div class="mc-location"><?php echo htmlspecialchars($row['Cinema_Name']); ?> &middot; <?php echo date("d M Y", strtotime($row['Date'])); ?></div>
                <?php if ($row['Status'] === 'coming'): ?>
                    <a href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>" class="btn btn-sm btn-ghost">Details</a>
                <?php else: ?>
                    <a href="booking.php?movie_id=<?php echo $row['Movie_ID']; ?>" class="book-now-btn">
                        <?php echo icon('ticket',15); ?> Book Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="empty-state"><div class="big"><?php echo icon('search',42); ?></div><p>No movies found<?php echo $q !== '' ? ' for “' . htmlspecialchars($q) . '”' : ''; ?>. <a href="movies.php" style="color:var(--gold);">Clear filters</a>.</p></div>
<?php endif; ?>
</div>

<script>
/* ============================================================
   Search-as-you-type (pure JavaScript — no AJAX/JSON).
   Filters the movie cards already rendered on the page as the
   user types. The server-side SQL search still works with JS off.
   ============================================================ */
(function () {
    var input = document.querySelector('.toolbar input[name="q"]');
    var grid  = document.querySelector('.movie-grid');
    if (!input || !grid) return;

    var cards = Array.prototype.slice.call(grid.querySelectorAll('.movie-card'));
    if (!cards.length) return;

    // "No match" message shown only while live-filtering
    var empty = document.createElement('div');
    empty.className = 'empty-state live-empty';
    empty.style.display = 'none';
    empty.innerHTML = '<p>No movies match your search.</p>';
    grid.parentNode.insertBefore(empty, grid.nextSibling);

    function norm(s) { return (s || '').toLowerCase().trim(); }

    function filter() {
        var q = norm(input.value), shown = 0;
        cards.forEach(function (card) {
            var hit = norm(card.getAttribute('data-title')).indexOf(q) !== -1;
            card.style.display = hit ? '' : 'none';
            if (hit) shown++;
        });
        empty.style.display = shown === 0 ? 'block' : 'none';
    }

    input.addEventListener('input', filter);
    // With JS on, don't reload the page — just filter instantly.
    var form = input.closest('form');
    if (form) form.addEventListener('submit', function (e) { e.preventDefault(); filter(); });

    filter(); // apply immediately (covers a pre-filled ?q)
})();
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
