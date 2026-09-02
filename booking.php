<?php
$pageTitle = "Book Tickets";
require_once 'includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$preselectMovie = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;

$movies = $conn->query("SELECT Movie_ID, Title, Date, Showtime FROM movie ORDER BY Date, Showtime");
$paymentTypes = $conn->query("SELECT Payment_Type_ID, Payment_Type FROM payment_type");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- Collect & sanitize form fields ----
    $fullName   = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $contact    = trim($_POST['contact'] ?? '');
    $movieId    = intval($_POST['movie_id'] ?? 0);
    $numTickets = intval($_POST['num_tickets'] ?? 0);
    $seats      = trim($_POST['seats'] ?? '');
    $paymentId  = intval($_POST['payment_type'] ?? 0);

    // ---- Server-side validation ----
    if ($fullName === '') $errors[] = "Full name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
    if (!preg_match('/^[0-9]{7,15}$/', $contact)) $errors[] = "Contact number must be 7-15 digits.";
    if ($movieId <= 0) $errors[] = "Please select a movie showtime.";
    if ($numTickets < 1 || $numTickets > 10) $errors[] = "Number of tickets must be between 1 and 10.";
    if ($seats === '') $errors[] = "Please specify seat numbers (e.g. A1, A2).";
    if ($paymentId <= 0) $errors[] = "Please select a payment method.";

    if (empty($errors)) {
        // Fetch movie showtime details for the reservation record
        $stmt = $conn->prepare("SELECT Title, Date, Showtime FROM movie WHERE Movie_ID = ?");
        $stmt->bind_param("i", $movieId);
        $stmt->execute();
        $movieRow = $stmt->get_result()->fetch_assoc();

        if (!$movieRow) {
            $errors[] = "Selected movie could not be found.";
        } else {
            // ---- Find or create customer record ----
            $custId = null;
            $stmt = $conn->prepare("SELECT Cust_ID FROM customer WHERE Email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $custResult = $stmt->get_result();

            if ($custResult->num_rows > 0) {
                $custId = $custResult->fetch_assoc()['Cust_ID'];
            } else {
                $nameParts = explode(" ", $fullName, 2);
                $fname = $nameParts[0];
                $lname = $nameParts[1] ?? '';
                $randomPass = password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO customer (Cust_Name, Cust_Lname, Cust_Number, Email, Password) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $fname, $lname, $contact, $email, $randomPass);
                $stmt->execute();
                $custId = $conn->insert_id;
            }

            // ---- Insert reservation ----
            $stmt = $conn->prepare("INSERT INTO reservation (Name, Time, Date, Cont_Num, Seats, Num_Tickets, Cust_ID, Movie_ID)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiii", $fullName, $movieRow['Showtime'], $movieRow['Date'], $contact, $seats, $numTickets, $custId, $movieId);
            $stmt->execute();
            $resId = $conn->insert_id;

            // ---- Insert transaction ----
            $ticketPrice = 12.50;
            $total = $ticketPrice * $numTickets;
            $today = date("Y-m-d");
            $stmt = $conn->prepare("INSERT INTO transaction (Cust_ID, Res_ID, Trans_Date, Start_Date, End_Date, Total_Payment, Payment_Type_ID)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssdi", $custId, $resId, $today, $today, $movieRow['Date'], $total, $paymentId);
            $stmt->execute();

            $_SESSION['cust_id'] = $custId;
            $_SESSION['cust_name'] = $fullName;

            header("Location: confirmation.php?res_id=" . $resId);
            exit;
        }
    }
}

include 'includes/header.php';
?>

<div class="form-panel">
    <h2>Book Your Tickets</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-msg">
            <ul style="margin-left:18px;">
                <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="booking.php" id="bookingForm">
        <div class="form-row">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ($_SESSION['cust_name'] ?? '')); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="contact">Contact Number</label>
            <input type="tel" id="contact" name="contact" pattern="[0-9]{7,15}" required
                   placeholder="e.g. 91234567" value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">
            <div class="hint">7-15 digits, numbers only.</div>
        </div>

        <div class="form-group">
            <label for="movie_id">Movie &amp; Showtime</label>
            <select id="movie_id" name="movie_id" required>
                <option value="">-- Select a showtime --</option>
                <?php while ($m = $movies->fetch_assoc()):
                    $selected = ($preselectMovie == $m['Movie_ID']) ? 'selected' : ''; ?>
                    <option value="<?php echo $m['Movie_ID']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($m['Title']); ?> &mdash;
                        <?php echo date("d M Y", strtotime($m['Date'])); ?>,
                        <?php echo date("g:i A", strtotime($m['Showtime'])); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="num_tickets">Number of Tickets</label>
                <input type="number" id="num_tickets" name="num_tickets" min="1" max="10" required
                       value="<?php echo htmlspecialchars($_POST['num_tickets'] ?? '1'); ?>">
            </div>
            <div class="form-group">
                <label for="seats">Seat Number(s)</label>
                <input type="text" id="seats" name="seats" required placeholder="e.g. A1, A2"
                       value="<?php echo htmlspecialchars($_POST['seats'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="payment_type">Payment Method</label>
            <select id="payment_type" name="payment_type" required>
                <option value="">-- Select payment method --</option>
                <?php while ($p = $paymentTypes->fetch_assoc()): ?>
                    <option value="<?php echo $p['Payment_Type_ID']; ?>">
                        <?php echo htmlspecialchars($p['Payment_Type']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn">Confirm Booking</button>
    </form>
</div>

<script>
// Client-side validation to catch obvious mistakes before submission
document.getElementById('bookingForm').addEventListener('submit', function (e) {
    const contact = document.getElementById('contact').value;
    const tickets = parseInt(document.getElementById('num_tickets').value, 10);
    let msg = "";

    if (!/^[0-9]{7,15}$/.test(contact)) {
        msg += "Contact number must be 7-15 digits.\n";
    }
    if (isNaN(tickets) || tickets < 1 || tickets > 10) {
        msg += "Number of tickets must be between 1 and 10.\n";
    }
    if (msg !== "") {
        alert(msg);
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
