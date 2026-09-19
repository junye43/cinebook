<?php
$pageTitle = "My Bookings";
require_once 'includes/db_connect.php';

require_login('my_bookings.php');
$custId = current_user_id();
$notice = "";

// Handle seat update (demonstrates SQL UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['res_code'], $_POST['new_seats'])) {
    if (!csrf_verify()) {
        $notice = "Your session expired. Please try again.";
    } else {
        $resCode = intval($_POST['res_code']);
        $newSeats = strtoupper(trim($_POST['new_seats']));
        if (!preg_match('/^[A-Z][0-9]{1,2}(,\s*[A-Z][0-9]{1,2})*$/', $newSeats)) {
            $notice = "Please enter valid seat(s), e.g. A1, A2.";
        } else {
            $stmt = $conn->prepare("UPDATE reservation SET Seats = ? WHERE Res_Code = ? AND Cust_ID = ?");
            $stmt->bind_param("sii", $newSeats, $resCode, $custId);
            $stmt->execute();
            $notice = $stmt->affected_rows > 0 ? "Seats updated." : "No change made.";
        }
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

<?php if ($notice !== ''): ?>
    <div class="success-msg"><?php echo htmlspecialchars($notice); ?></div>
<?php endif; ?>

<div class="table-wrap">
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
                        <form method="POST" action="my_bookings.php" style="display:flex;gap:8px;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="res_code" value="<?php echo $b['Res_Code']; ?>">
                            <input type="text" name="new_seats" placeholder="e.g. A1, A2" style="width:120px;padding:8px 10px;border-radius:8px;border:1px solid var(--border);background:var(--bg-3);color:var(--text);">
                            <button type="submit" class="btn btn-sm">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">You have no bookings yet. <a href="movies.php" style="color:var(--gold);">Browse movies</a>.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
