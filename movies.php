<?php
$pageTitle = "Movies";
require_once 'includes/db_connect.php';
include 'includes/header.php';

// Optional filter by cinema branch
$cinemaFilter = isset($_GET['cinema']) ? intval($_GET['cinema']) : 0;

$cinemas = $conn->query("SELECT Cinema_ID, Cinema_Name FROM cinema ORDER BY Cinema_Name");

$sql = "SELECT m.Movie_ID, m.Title, m.Genre, m.Duration_Min, m.Date, m.Showtime,
               m.Location, c.Cinema_Name, b.Bran_Location
        FROM movie m
        LEFT JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
        LEFT JOIN branch b ON c.Bran_ID = b.Bran_ID";
if ($cinemaFilter > 0) {
    $sql .= " WHERE m.Cinema_ID = " . $cinemaFilter;
}
$sql .= " ORDER BY m.Date ASC, m.Showtime ASC";
$result = $conn->query($sql);
?>

<h1 class="section-title">All Movies &amp; Showtimes</h1>

<form method="GET" action="movies.php" style="margin-bottom:20px;">
    <label for="cinema" style="margin-right:8px;">Filter by cinema:</label>
    <select name="cinema" id="cinema" onchange="this.form.submit()" style="padding:8px;border-radius:6px;">
        <option value="0">All Cinemas</option>
        <?php while ($c = $cinemas->fetch_assoc()): ?>
            <option value="<?php echo $c['Cinema_ID']; ?>" <?php echo ($cinemaFilter == $c['Cinema_ID']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['Cinema_Name']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</form>

<table class="data-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Genre</th>
            <th>Duration</th>
            <th>Cinema</th>
            <th>Branch</th>
            <th>Date</th>
            <th>Showtime</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Title']); ?></td>
                    <td><?php echo htmlspecialchars($row['Genre']); ?></td>
                    <td><?php echo intval($row['Duration_Min']); ?> min</td>
                    <td><?php echo htmlspecialchars($row['Cinema_Name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Bran_Location']); ?></td>
                    <td><?php echo date("d M Y", strtotime($row['Date'])); ?></td>
                    <td><?php echo date("g:i A", strtotime($row['Showtime'])); ?></td>
                    <td><a href="movie_details.php?id=<?php echo $row['Movie_ID']; ?>" class="btn" style="padding:6px 14px;">View</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">No movies found for this cinema.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; $conn->close(); ?>
