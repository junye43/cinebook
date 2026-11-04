<?php
$pageTitle = "About Us";
require_once 'includes/db_connect.php';
include 'includes/header.php';
?>

<img class="theatres-banner"
     src="images/ui/theatres-banner.png"
     alt="CineBook Cinemas">

<h1 class="section-title">About CineBook</h1>
<div class="about-grid">
    <div class="about-copy">
        <p class="about-intro">
        CineBook is an online cinema ticket booking platform that lets movie-goers browse showtimes
        across multiple branches, reserve seats, and pay securely &mdash; all without queuing at the
        box office. Built as part of the IE4727 Web Application Design project (Theme 5: Booking
        Cinema Tickets/Theatre Shows), CineBook models cinemas, branches, movies, customers,
        reservations and transactions to deliver a smooth end-to-end booking experience.
        </p>
    </div>
    <div class="about-features">
        <div class="about-feat">
            <span class="af-ico"><?php echo icon('search', 22); ?></span>
            <div><h3>Browse Showtimes</h3>
            <p>Explore now-showing and upcoming films across all our cinema branches.</p></div>
        </div>
        <div class="about-feat">
            <span class="af-ico"><?php echo icon('ticket', 22); ?></span>
            <div><h3>Pick Your Seats</h3>
            <p>Choose exactly where you sit with a live, interactive seat map.</p></div>
        </div>
        <div class="about-feat">
            <span class="af-ico"><?php echo icon('clock', 22); ?></span>
            <div><h3>Book in Seconds</h3>
            <p>Your details are saved to your account for a fast, fuss-free checkout.</p></div>
        </div>
    </div>
</div>

<h2 class="section-title">Our Branches</h2>
<p class="section-sub">Find a CineBook cinema near you.</p>
<div class="table-wrap">
<table class="data-table branches-table">
    <thead><tr><th>Cinema</th><th>Branch Location</th><th>Contact</th></tr></thead>
    <tbody>
    <?php
    $res = $conn->query("SELECT c.Cinema_Name, b.Bran_Location, c.Cinema_Cont
                          FROM cinema c LEFT JOIN branch b ON c.Bran_ID = b.Bran_ID");
    while ($row = $res->fetch_assoc()):
    ?>
        <tr>
            <td>
                <span class="cell-ic"><?php echo icon('film', 18); ?>
                    <span class="cinema-name"><?php echo htmlspecialchars($row['Cinema_Name']); ?></span>
                </span>
            </td>
            <td><span class="cell-ic"><?php echo icon('pin', 17); ?><?php echo htmlspecialchars($row['Bran_Location']); ?></span></td>
            <td><span class="cell-ic contact-pill"><?php echo icon('phone', 17); ?><?php echo htmlspecialchars($row['Cinema_Cont']); ?></span></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
