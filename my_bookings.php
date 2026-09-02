<?php
$pageTitle = "My Bookings";
require_once 'includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['cust_id'])) {
    header("Location: login.php");
    exit;
}
$custId = intval($_SESSION['cust_id']);

// Handle seat update (demonstrates SQL UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['res_code'], $_POST['new_seats'])) {
    $resCode = intval($_POST['res_code']);
    $newSeats = trim($_POST['new_seats']);
    if ($newSeats !== '') {
        $stmt = $conn->prepare("UPDATE reservation SET Seats = ? WHERE Res_Code = ? AND Cust_ID = ?");
        $stmt->bind_param("sii", $newSeats, $resCode, $custId);
        $stmt->execute();
    }
}

$stmt = $conn->prepare("SELECT r.Res_Code, r.Date, r.Time, r.Seats, r.Num_Tickets, m.Title, t.Total_Payment
                         FROM reservation r
                         JOIN movie m ON r.Movie_ID = m.Movie_ID
                         LEFT JOIN transaction t ON t.Res_ID = r.Res_Code
                         WHERE r.Cust_ID = ?
                         ORDER BY r.Date DESC");
$stmt->bind_param("i", $custId);
$stmt->execute();
$bookings = $stmt->get_result();

include 'includes/header.php';
?>

<h1 class="section-title">My Bookings</h1>

<table class="data-table">
    <thead>
        <tr><th>Movie</th><th>Date</th><th>Time</th><th>Seats</th><th>Tickets</th><th>Total Paid</th><th>Update Seats</th></tr>
    </thead>
    <tbody>
        <?php if ($bookings->num_rows > 0): ?>
            <?php while ($b = $bookings->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['Title']); ?></td>
                    <td><?php echo date("d M Y", strtotime($b['Date'])); ?></td>
                    <td><?php echo date("g:i A", strtotime($b['Time'])); ?></td>
                    <td><?php echo htmlspecialchars($b['Seats']); ?></td>
                    <td><?php echo intval($b['Num_Tickets']); ?></td>
                    <td>$<?php echo number_format($b['Total_Payment'] ?? 0, 2); ?></td>
                    <td>
                        <form method="POST" action="my_bookings.php" style="display:flex;gap:6px;">
                            <input type="hidden" name="res_code" value="<?php echo $b['Res_Code']; ?>">
                            <input type="text" name="new_seats" placeholder="New seats" style="width:100px;padding:6px;border-radius:6px;">
                            <button type="submit" class="btn" style="padding:6px 12px;">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">You have no bookings yet. <a href="movies.php" style="color:var(--gold);">Browse movies</a>.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; $conn->close(); ?>
