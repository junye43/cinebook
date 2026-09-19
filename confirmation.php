<?php
$pageTitle = "Booking Confirmation";
require_once 'includes/db_connect.php';

$resId = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;

$stmt = $conn->prepare("SELECT r.Res_Code, r.Name, r.Date, r.Time, r.Seats, r.Num_Tickets,
                                m.Title, m.Genre, c.Cinema_Name, t.Total_Payment, t.Trans_No, p.Payment_Type
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
    <div class="confirm-wrap">
        <h2>Congratulations!</h2>
        <p>Your booking is confirmed. Thank you for choosing CineBook, <?php echo htmlspecialchars($booking['Name']); ?>!</p>

        <div class="ticket">
            <span class="ticket-badge"><?php echo intval($booking['Num_Tickets']); ?> movie ticket(s)</span>
            <div class="ticket-grid">
                <div><div class="lbl">Movie</div><div class="val"><?php echo htmlspecialchars($booking['Title']); ?></div></div>
                <div><div class="lbl">Date</div><div class="val"><?php echo date("d M Y", strtotime($booking['Date'])); ?></div></div>
                <div><div class="lbl">Time</div><div class="val"><?php echo date("g:i A", strtotime($booking['Time'])); ?></div></div>
                <div><div class="lbl">Venue</div><div class="val"><?php echo htmlspecialchars($booking['Cinema_Name']); ?></div></div>
                <div><div class="lbl">Seats</div><div class="val"><?php echo htmlspecialchars($booking['Seats']); ?></div></div>
                <div><div class="lbl">Total Paid</div><div class="val">$<?php echo number_format($booking['Total_Payment'], 2); ?></div></div>
                <div><div class="lbl">Booking Ref</div><div class="val">RES-<?php echo str_pad($booking['Res_Code'], 5, '0', STR_PAD_LEFT); ?></div></div>
                <div><div class="lbl">Payment</div><div class="val"><?php echo htmlspecialchars($booking['Payment_Type']); ?></div></div>
                <div><div class="lbl">Txn No.</div><div class="val">TXN-<?php echo str_pad($booking['Trans_No'], 6, '0', STR_PAD_LEFT); ?></div></div>
            </div>
            <div class="ticket-perf"></div>
            <div class="barcode" aria-hidden="true"></div>
        </div>

        <div class="confirm-actions">
            <a href="movies.php" class="btn">Book Another Movie</a>
            <a href="index.php" class="btn btn-ghost">Back to Home</a>
        </div>
    </div>
<?php else: ?>
    <div class="error-msg">Booking not found. Please try booking again.</div>
<?php endif; ?>

<?php include 'includes/footer.php'; $conn->close(); ?>
