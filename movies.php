<?php
$pageTitle = "Movies";
require_once 'includes/db_connect.php';
include 'includes/header.php';

// Location (outlet/branch), genre and title filters.
$locFilter = ctype_digit((string)($_GET['loc'] ?? '')) ? (int)$_GET['loc'] : 0;
$genre = is_string($_GET['genre'] ?? null) ? substr(trim($_GET['genre']), 0, 40) : '';
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$q = substr($q, 0, 100);

$locations = $conn->query("SELECT Bran_ID, Bran_Location FROM branch ORDER BY Bran_Location");
$genreList = $conn->query("SELECT DISTINCT Genre FROM movie WHERE Genre IS NOT NULL AND Genre <> '' ORDER BY Genre");

// One representative row per title, honouring the active filters so the chosen
// outlet/genre decides which show stands in for each title.
$inner = "SELECT MIN(m2.Movie_ID) FROM movie m2
          LEFT JOIN cinema c2 ON m2.Cinema_ID = c2.Cinema_ID
          WHERE 1=1";
$params = [];
$types = "";
if ($q !== '')      { $inner .= " AND m2.Title LIKE ?"; $params[] = "%$q%";     $types .= "s"; }
if ($genre !== '')  { $inner .= " AND m2.Genre = ?";    $params[] = $genre;      $types .= "s"; }
if ($locFilter > 0) { $inner .= " AND c2.Bran_ID = ?";  $params[] = $locFilter;  $types .= "i"; }
$inner .= " GROUP BY m2.Title";

$sql = "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
               m.Certificate, m.Stars, m.Status, m.Poster, c.Cinema_Name, b.Bran_Location
        FROM movie m
        LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
        LEFT JOIN branch b ON c.Bran_ID = b.Bran_ID
        WHERE m.Movie_ID IN ($inner)
        ORDER BY m.Date ASC, m.Showtime ASC";

$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Friendly name of the selected outlet, for the heading.
$locName = '';
if ($locFilter > 0) {
    $ls = $conn->prepare("SELECT Bran_Location FROM branch WHERE Bran_ID = ?");
    $ls->bind_param('i', $locFilter);
    $ls->execute();
    $locName = (string) ($ls->get_result()->fetch_assoc()['Bran_Location'] ?? '');
}
?>

<div class="section-head">
    <h2><?php
        if ($q !== '') echo 'Results for “' . htmlspecialchars($q) . '”';
        elseif ($locName !== '') echo 'Now Playing at ' . htmlspecialchars($locName);
        else echo 'All Movies &amp; Showtimes';
    ?></h2>
</div>

<form class="toolbar" method="GET" action="movies.php" role="search">
    <div class="filter-group">
        <label for="loc">Location</label>
        <select name="loc" id="loc" onchange="this.form.submit()">
            <option value="0">All Locations</option>
            <?php while ($b = $locations->fetch_assoc()): ?>
                <option value="<?php echo $b['Bran_ID']; ?>" <?php echo ($locFilter == $b['Bran_ID']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($b['Bran_Location']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="filter-group">
        <label for="genre">Genre</label>
        <select name="genre" id="genre" onchange="this.form.submit()">
            <option value="">All Genres</option>
            <?php while ($g = $genreList->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($g['Genre']); ?>" <?php echo ($genre === $g['Genre']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($g['Genre']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="filter-group grow">
        <label for="q">Search</label>
        <div class="search-row">
            <input type="search" name="q" id="q" placeholder="Search by title…" value="<?php echo htmlspecialchars($q); ?>">
            <button type="submit" class="btn btn-sm">Search</button>
        </div>
    </div>
</form>

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
                <div class="mc-location"><?php
                    echo htmlspecialchars($locFilter > 0 ? $row['Bran_Location'] : $row['Genre']);
                    echo ' &middot; ';
                    echo $row['Status'] === 'coming' ? 'Opens ' . date("d M", strtotime($row['Date'])) : date("d M Y", strtotime($row['Date']));
                ?></div>
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

    // Live filter as the user types (instant, client-side). The Search button
    // and the Location/Genre dropdowns still submit normally for a server query
    // (so it also works with JavaScript off, and the dropdowns can reload).
    input.addEventListener('input', filter);

    filter(); // apply immediately (covers a pre-filled ?q)
})();
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
