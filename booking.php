<?php
$pageTitle = "Book Tickets";
require_once 'includes/db_connect.php';

$errors = [];
$preselectMovie = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;

// ---- Require login before booking ----
$returnTo = 'booking.php' . ($preselectMovie ? '?movie_id=' . $preselectMovie : '');
require_login($returnTo);
$custId = current_user_id();

// ---- Prefill the form from the logged-in customer ----
$stmt = $conn->prepare("SELECT Cust_Name, Cust_Lname, Email, Cust_Number FROM customer WHERE Cust_ID = ?");
$stmt->bind_param("i", $custId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc() ?: [];
$prefillName    = trim(($me['Cust_Name'] ?? '') . ' ' . ($me['Cust_Lname'] ?? ''));
$prefillEmail   = $me['Email'] ?? '';
$prefillContact = $me['Cust_Number'] ?? '';

$movies = $conn->query("SELECT Movie_ID, Title, Date, Showtime FROM movie WHERE Status = 'showing' ORDER BY Date, Showtime");
$paymentTypes = $conn->query("SELECT Payment_Type_ID, Payment_Type FROM payment_type");

// ---- Build "already booked" seat map per showtime (server-side, no AJAX) ----
// Each reservation stores seats as free text like "A1, A2". We parse them into
// a per-movie list so the seat picker can grey out taken seats on the client.
$occupiedByMovie = [];
$occRes = $conn->query("SELECT Movie_ID, Seats FROM reservation WHERE Movie_ID IS NOT NULL");
if ($occRes) {
    while ($o = $occRes->fetch_assoc()) {
        $mid = (int) $o['Movie_ID'];
        foreach (preg_split('/[\s,]+/', trim($o['Seats'])) as $seat) {
            $seat = strtoupper(trim($seat));
            if ($seat !== '') $occupiedByMovie[$mid][] = $seat;
        }
    }
}
$TICKET_PRICE = 12.50;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- CSRF check ----
    if (!csrf_verify()) $errors[] = "Your session expired. Please submit the form again.";

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
    if ($seats === '') $errors[] = "Please select your seats.";
    if (!preg_match('/^[A-Z][0-9]{1,2}(,\s*[A-Z][0-9]{1,2})*$/', $seats)) $errors[] = "Seat format looks invalid.";
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
            // Booking is tied to the logged-in customer ($custId).

            // ---- Insert reservation ----
            $stmt = $conn->prepare("INSERT INTO reservation (Name, Time, Date, Cont_Num, Seats, Num_Tickets, Cust_ID, Movie_ID)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiii", $fullName, $movieRow['Showtime'], $movieRow['Date'], $contact, $seats, $numTickets, $custId, $movieId);
            $stmt->execute();
            $resId = $conn->insert_id;

            // ---- Insert transaction ----
            $total = $TICKET_PRICE * $numTickets;
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

<h1 class="detail-title" style="font-size:1.9em;margin-bottom:4px;">Book Your Tickets</h1>
<p class="form-sub">Choose a showtime, pick your seats, and confirm your booking.</p>

<?php if (!empty($errors)): ?>
    <div class="error-msg">
        <ul style="margin-left:18px;">
            <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="booking.php" id="bookingForm">
<?php echo csrf_field(); ?>
<div class="booking-layout">

    <!-- LEFT: customer + showtime details -->
    <div class="panel">
        <h3>Your Details</h3>
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? $prefillName); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? $prefillEmail); ?>">
        </div>
        <div class="form-group">
            <label for="contact">Contact Number</label>
            <input type="tel" id="contact" name="contact" pattern="[0-9]{7,15}" required
                   placeholder="e.g. 91234567" value="<?php echo htmlspecialchars($_POST['contact'] ?? $prefillContact); ?>">
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
                        <?php echo date("d M", strtotime($m['Date'])); ?>,
                        <?php echo date("g:i A", strtotime($m['Showtime'])); ?>
                    </option>
                <?php endwhile; ?>
            </select>
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
    </div>

    <!-- CENTER: seat map -->
    <div class="panel">
        <div class="screen-wrap">
            <div class="screen-curve"></div>
            <div class="screen-label">SCREEN</div>
        </div>
        <div style="overflow-x:auto;">
            <div id="seatGrid" class="seat-grid"><!-- seats injected by JS --></div>
        </div>
        <div class="seat-legend">
            <span><i class="dot available"></i> Available</span>
            <span><i class="dot reserved"></i> Reserved</span>
            <span><i class="dot selected"></i> Selected</span>
        </div>
        <div id="seatHint" class="hint" style="text-align:center;">Select a showtime, then pick your seats.</div>
    </div>

    <!-- RIGHT: live booking summary -->
    <div class="panel">
        <h3>Booking Summary</h3>
        <div class="summary-row"><span>Movie</span><span class="val" id="sumMovie">—</span></div>
        <div class="summary-row"><span>Seats</span><span class="val" id="sumSeats">—</span></div>
        <div class="summary-row"><span>Ticket Price</span><span>$<?php echo number_format($TICKET_PRICE, 2); ?></span></div>
        <div class="summary-row"><span>Quantity</span><span id="sumQty">0</span></div>
        <div class="summary-row"><span>Convenience Fee</span><span class="val">FREE</span></div>
        <div class="summary-row total"><span>Total</span><span class="val" id="sumTotal">$0.00</span></div>

        <input type="hidden" id="seats" name="seats" value="<?php echo htmlspecialchars($_POST['seats'] ?? ''); ?>">
        <input type="hidden" id="num_tickets" name="num_tickets" value="<?php echo htmlspecialchars($_POST['num_tickets'] ?? '0'); ?>">

        <button type="submit" class="btn btn-block" style="margin-top:18px;">Confirm Booking</button>
    </div>

</div>
</form>

<script>
/* ============================================================
   Interactive seat picker (pure JavaScript — no AJAX/JSON)
   The occupied-seat map is written directly into JS by PHP.
   ============================================================ */

// Seats already booked, grouped by Movie_ID (generated server-side by PHP)
var occupiedSeats = {
<?php foreach ($occupiedByMovie as $mid => $seats): ?>
    "<?php echo (int)$mid; ?>": [<?php
        echo implode(',', array_map(function ($s) { return '"' . addslashes($s) . '"'; }, $seats));
    ?>],
<?php endforeach; ?>
};

var TICKET_PRICE = <?php echo number_format($TICKET_PRICE, 2, '.', ''); ?>;
var ROWS = ["A", "B", "C", "D", "E", "F", "G", "H"];
var COLS = 10;              // seats per row
var MAX_SEATS = 10;         // matches server-side validation

var grid        = document.getElementById("seatGrid");
var seatsInput  = document.getElementById("seats");
var ticketsInput = document.getElementById("num_tickets");
var movieSelect = document.getElementById("movie_id");
var sumMovie = document.getElementById("sumMovie");
var sumSeats = document.getElementById("sumSeats");
var sumQty   = document.getElementById("sumQty");
var sumTotal = document.getElementById("sumTotal");
var hintEl   = document.getElementById("seatHint");

var selected = [];  // currently selected seat labels

function currentOccupied() {
    return occupiedSeats[movieSelect.value] || [];
}

function buildGrid() {
    grid.innerHTML = "";
    var taken = currentOccupied();

    ROWS.forEach(function (row) {
        var rowEl = document.createElement("div");
        rowEl.className = "seat-row";

        var label = document.createElement("span");
        label.className = "row-label";
        label.textContent = row;
        rowEl.appendChild(label);

        for (var i = 1; i <= COLS; i++) {
            var code = row + i;
            var seat = document.createElement("button");
            seat.type = "button";
            seat.className = "seat";
            seat.setAttribute("data-seat", code);
            seat.setAttribute("aria-label", "Seat " + code);

            if (taken.indexOf(code) !== -1) {
                seat.classList.add("occupied");
                seat.disabled = true;
            } else if (selected.indexOf(code) !== -1) {
                seat.classList.add("selected");
            }
            seat.addEventListener("click", onSeatClick);

            rowEl.appendChild(seat);
            if (i === 5) {  // aisle gap in the middle
                var aisle = document.createElement("span");
                aisle.className = "aisle";
                rowEl.appendChild(aisle);
            }
        }
        grid.appendChild(rowEl);
    });
}

function onSeatClick() {
    var code = this.getAttribute("data-seat");
    var idx = selected.indexOf(code);

    if (idx !== -1) {
        selected.splice(idx, 1);
        this.classList.remove("selected");
    } else {
        if (selected.length >= MAX_SEATS) {
            alert("You can select a maximum of " + MAX_SEATS + " seats.");
            return;
        }
        selected.push(code);
        this.classList.add("selected");
    }
    updateSummary();
}

function updateSummary() {
    selected.sort();
    seatsInput.value = selected.join(", ");
    ticketsInput.value = selected.length;

    var opt = movieSelect.options[movieSelect.selectedIndex];
    sumMovie.textContent = movieSelect.value ? opt.text.split("—")[0].trim() : "—";
    sumSeats.textContent = selected.length ? selected.join(", ") : "—";
    sumQty.textContent = selected.length;
    sumTotal.textContent = "$" + (selected.length * TICKET_PRICE).toFixed(2);

    if (!movieSelect.value) {
        hintEl.textContent = "Select a showtime, then pick your seats.";
    } else if (selected.length === 0) {
        hintEl.textContent = "Pick one or more available seats.";
    } else {
        hintEl.textContent = selected.length + " seat(s) selected.";
    }
}

// Rebuild the map whenever the showtime changes (occupancy differs per movie)
movieSelect.addEventListener("change", function () {
    selected = [];
    buildGrid();
    updateSummary();
});

// Restore any seats kept after a failed server-side validation
(function restore() {
    var prev = seatsInput.value.trim();
    if (prev) {
        selected = prev.split(/[\s,]+/).filter(function (s) { return s; });
    }
    buildGrid();
    updateSummary();
})();

// ---- Submit validation (client-side, before the form is sent) ----
document.getElementById("bookingForm").addEventListener("submit", function (e) {
    var contact = document.getElementById("contact").value;
    var msg = "";

    if (!movieSelect.value) msg += "Please select a movie showtime.\n";
    if (!/^[0-9]{7,15}$/.test(contact)) msg += "Contact number must be 7-15 digits.\n";
    if (selected.length < 1) msg += "Please select at least one seat.\n";
    if (selected.length > MAX_SEATS) msg += "You can book a maximum of " + MAX_SEATS + " seats.\n";

    if (msg !== "") {
        alert(msg);
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
