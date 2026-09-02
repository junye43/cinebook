<?php
$pageTitle = "About Us";
include 'includes/header.php';
?>

<h1 class="section-title">About CineBook</h1>
<p style="max-width:700px;">
CineBook is an online cinema ticket booking platform that lets movie-goers browse showtimes
across multiple branches, reserve seats, and pay securely &mdash; all without queuing at the
box office. Built as part of the IE4727 Web Application Design project (Theme 5: Booking
Cinema Tickets/Theatre Shows), CineBook models cinemas, branches, movies, customers,
reservations and transactions to deliver a smooth end-to-end booking experience.
</p>

<h2 class="section-title">Our Branches</h2>
<table class="data-table">
    <thead><tr><th>Cinema</th><th>Branch Location</th><th>Contact</th></tr></thead>
    <tbody>
    <?php
    require_once 'includes/db_connect.php';
    $res = $conn->query("SELECT c.Cinema_Name, b.Bran_Location, c.Cinema_Cont
                          FROM cinema c LEFT JOIN branch b ON c.Bran_ID = b.Bran_ID");
    while ($row = $res->fetch_assoc()):
    ?>
        <tr>
            <td><?php echo htmlspecialchars($row['Cinema_Name']); ?></td>
            <td><?php echo htmlspecialchars($row['Bran_Location']); ?></td>
            <td><?php echo htmlspecialchars($row['Cinema_Cont']); ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php include 'includes/footer.php'; $conn->close(); ?>
