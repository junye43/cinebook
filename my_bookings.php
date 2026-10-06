<?php
$pageTitle = "My Bookings";
require_once 'includes/db_connect.php';

require_login('my_bookings.php');
$custId = current_user_id();

$stmt = $conn->prepare("SELECT r.Res_Code, r.Date, r.Time, r.Seats, r.Num_Tickets, m.Title, m.Poster, m.Location, t.Total_Payment
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

<div class="table-wrap">
<table class="data-table">
    <thead>
        <tr><th></th><th>Movie</th><th>Hall</th><th>Date</th><th>Time</th><th>Seats</th><th>Tickets</th><th>Total Paid</th><th></th></tr>
    </thead>
    <tbody>
        <?php if ($bookings->num_rows > 0): ?>
            <?php while ($b = $bookings->fetch_assoc()):
                $view = 'confirmation.php?res_id=' . $b['Res_Code']; ?>
                <tr class="clickable-row" data-href="<?php echo $view; ?>">
                    <td>
                        <?php if (poster_file($b['Poster']) !== ''): ?>
                            <img class="poster-thumb" src="images/posters/<?php echo htmlspecialchars(rawurlencode($b['Poster'])); ?>" alt="<?php echo htmlspecialchars($b['Title']); ?> poster">
                        <?php endif; ?>
                    </td>
                    <td><a href="<?php echo $view; ?>" class="booking-link"><?php echo htmlspecialchars($b['Title']); ?></a></td>
                    <td><?php echo htmlspecialchars($b['Location']); ?></td>
                    <td><?php echo date("d M Y", strtotime($b['Date'])); ?></td>
                    <td><?php echo date("g:i A", strtotime($b['Time'])); ?></td>
                    <td><?php echo htmlspecialchars($b['Seats']); ?></td>
                    <td><?php echo intval($b['Num_Tickets']); ?></td>
                    <td>$<?php echo number_format($b['Total_Payment'] ?? 0, 2); ?></td>
                    <td><a href="<?php echo $view; ?>" class="btn btn-sm">View</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9">You have no bookings yet. <a href="movies.php" style="color:var(--gold);">Browse movies</a>.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
