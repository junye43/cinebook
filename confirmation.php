<?php
$pageTitle = "Booking Confirmation";
require_once 'includes/db_connect.php';

$resId = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;

$stmt = $conn->prepare("SELECT r.Res_Code, r.Name, r.Date, r.Time, r.Seats, r.Num_Tickets,
                                m.Title, c.Cinema_Name, t.Total_Payment, t.Trans_No, p.Payment_Type
                         FROM reservation r
                         JOIN movie m ON r.Movie_ID = m.Movie_ID
                         JOIN cinema c ON m.Cinema_ID = c.Cinema_ID
                         JOIN transaction t ON t.Res_ID = r.Res_Code
                         JOIN payment_type p ON t.Payment_Type_ID = p.Payment_Type_ID
                         WHERE r.Res_Code = ?");
$stmt->bind_param("i", $resId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

include 'includes/header.php';
?>

<?php if ($booking): ?>
    <div class="confirm-box">
        <h2>&#10003; Booking Confirmed!</h2>
        <p>Thank you, <?php echo htmlspecialchars($booking['Name']); ?>. Your tickets have been reserved.</p>

        <table class="data-table" style="margin-top:18px;">
            <tr><th>Booking Reference</th><td>RES-<?php echo str_pad($booking['Res_Code'], 5, '0', STR_PAD_LEFT); ?></td></tr>
            <tr><th>Movie</th><td><?php echo htmlspecialchars($booking['Title']); ?></td></tr>
            <tr><th>Cinema</th><td><?php echo htmlspecialchars($booking['Cinema_Name']); ?></td></tr>
            <tr><th>Date</th><td><?php echo date("d M Y", strtotime($booking['Date'])); ?></td></tr>
            <tr><th>Time</th><td><?php echo date("g:i A", strtotime($booking['Time'])); ?></td></tr>
            <tr><th>Seats</th><td><?php echo htmlspecialchars($booking['Seats']); ?></td></tr>
            <tr><th>Tickets</th><td><?php echo intval($booking['Num_Tickets']); ?></td></tr>
            <tr><th>Payment Method</th><td><?php echo htmlspecialchars($booking['Payment_Type']); ?></td></tr>
            <tr><th>Total Paid</th><td>$<?php echo number_format($booking['Total_Payment'], 2); ?></td></tr>
            <tr><th>Transaction No.</th><td>TXN-<?php echo str_pad($booking['Trans_No'], 6, '0', STR_PAD_LEFT); ?></td></tr>
        </table>

        <p style="margin-top:20px;">
            <a href="movies.php" class="btn">Book Another Movie</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </p>
    </div>
<?php else: ?>
    <div class="error-msg">Booking not found. Please try booking again.</div>
<?php endif; ?>

<?php include 'includes/footer.php'; $conn->close(); ?>
