<?php
$pageTitle = "Book Tickets";
require_once 'includes/db_connect.php';

$errors = [];
$preselectMovie = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;

// ---- Require login before booking ----
require_once 'includes/booking_validation.php';
$returnTo = 'booking.php' . ($preselectMovie ? '?movie_id=' . $preselectMovie : '');
require_login($returnTo);
$custId = (int) current_user_id();

// ---- Prefill customer details ----
$stmt = $conn->prepare("SELECT Cust_Name, Cust_Lname, Cust_Age, Cust_DOB, Email, Cust_Number FROM customer WHERE Cust_ID = ?");
$stmt->bind_param("i", $custId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
if (!$me) {
    session_unset();
    header('Location: login.php?redirect=booking.php');
    exit;
}
$prefillName = trim($me['Cust_Name'] . ' ' . $me['Cust_Lname']);
$prefillEmail = $me['Email'];
$prefillContact = $me['Cust_Number'];
$myAge = age_from_dob($me['Cust_DOB'] ?? '');
if ($myAge === null) $myAge = (int) ($me['Cust_Age'] ?? 0);

// Cinema showtimes are Singapore local time.
$nowSingapore = new DateTimeImmutable('now', new DateTimeZone('Asia/Singapore'));
$nowStr = $nowSingapore->format('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT Movie_ID, Title, Date, Showtime, Certificate FROM movie
                        WHERE Status = 'showing' AND TIMESTAMP(Date, Showtime) > ?
                        ORDER BY Date, Showtime");
$stmt->bind_param('s', $nowStr);
$stmt->execute();
$moviesRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$bookMovie = null;
if ($preselectMovie) {
    $st = $conn->prepare("SELECT Title, Genre, Poster, Backdrop, Certificate FROM movie WHERE Movie_ID = ?");
    $st->bind_param('i', $preselectMovie);
    $st->execute();
    $bookMovie = $st->get_result()->fetch_assoc();
}
$paymentTypes = $conn->query("SELECT Payment_Type_ID, Payment_Type FROM payment_type ORDER BY Payment_Type_ID");

$TICKET_PRICE = 12.50;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) $errors[] = 'Your session expired. Please submit the form again.';

    $fullName = cb_post_text('full_name');
    $email = cb_post_text('email');
    $contact = cb_post_text('contact');
    $movieId = cb_post_positive_int('movie_id');
    $numTickets = cb_post_positive_int('num_tickets');
    $seatsText = cb_post_text('seats');
    $paymentId = cb_post_positive_int('payment_type');

    if ($fullName === '') $errors[] = 'Full name is required.';
    elseif (strlen($fullName) > 100) $errors[] = 'Full name must be 100 characters or fewer.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) $errors[] = 'Please enter a valid email (maximum 120 characters).';
    if (!preg_match('/^[0-9]{7,15}$/D', $contact)) $errors[] = 'Contact number must contain 7–15 digits.';
    if ($movieId <= 0) $errors[] = 'Please select a movie showtime.';
    if ($numTickets < 1 || $numTickets > 10) $errors[] = 'Number of tickets must be between 1 and 10.';
    $seatCheck = cb_validate_seats($seatsText, $numTickets);
    $errors = array_merge($errors, $seatCheck['errors']);
    if ($paymentId <= 0) $errors[] = 'Please select a payment method.';

    if (!$errors) {
        $transactionStarted = false;
        try {
            $conn->begin_transaction();
            $transactionStarted = true;

            // Serializes all reservations for the same showtime (same Movie_ID).
            $stmt = $conn->prepare("SELECT Title, Date, Showtime, Certificate, Status FROM movie WHERE Movie_ID = ? FOR UPDATE");
            $stmt->bind_param('i', $movieId);
            $stmt->execute();
            $movieRow = $stmt->get_result()->fetch_assoc();

            if (!$movieRow) $errors[] = 'The selected showtime could not be found.';
            elseif ($movieRow['Status'] !== 'showing') $errors[] = 'This film is not open for booking.';
            elseif (!cb_showtime_is_future($movieRow['Date'], $movieRow['Showtime'])) {
                $errors[] = 'This showtime has already passed. Please choose another time.';
            }
            if ($movieRow && $myAge < cert_min_age($movieRow['Certificate'])) {
                $errors[] = 'This film is rated ' . $movieRow['Certificate'] . ' — you must be at least ' . cert_min_age($movieRow['Certificate']) . ' to book it.';
            }

            $stmt = $conn->prepare('SELECT Payment_Type_ID FROM payment_type WHERE Payment_Type_ID = ?');
            $stmt->bind_param('i', $paymentId);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) $errors[] = 'Please select a valid payment method.';

            if (!$errors) {
                $stmt = $conn->prepare('SELECT Seat_Code FROM reservation_seat WHERE Movie_ID = ?');
                $stmt->bind_param('i', $movieId);
                $stmt->execute();
                $taken = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'Seat_Code');
                $conflicts = array_intersect($seatCheck['seats'], $taken);
                if ($conflicts) {
                    $errors[] = 'Seat ' . implode(', ', $conflicts) . ' was just booked. Please select another seat and try again.';
                }
            }

            if (!$errors) {
                $seats = implode(', ', $seatCheck['seats']);
                $stmt = $conn->prepare('INSERT INTO reservation (Name, Time, Date, Cont_Num, Seats, Num_Tickets, Cust_ID, Movie_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssssiii', $fullName, $movieRow['Showtime'], $movieRow['Date'], $contact, $seats, $numTickets, $custId, $movieId);
                $stmt->execute();
                $resId = $conn->insert_id;

                $stmt = $conn->prepare('INSERT INTO reservation_seat (Res_Code, Movie_ID, Seat_Code) VALUES (?, ?, ?)');
                foreach ($seatCheck['seats'] as $seat) {
                    $stmt->bind_param('iis', $resId, $movieId, $seat);
                    $stmt->execute();
                }

                $total = $TICKET_PRICE * $numTickets;
                $today = $nowSingapore->format('Y-m-d');
                $stmt = $conn->prepare('INSERT INTO transaction (Cust_ID, Res_ID, Trans_Date, Start_Date, End_Date, Total_Payment, Payment_Type_ID) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('iisssdi', $custId, $resId, $today, $today, $movieRow['Date'], $total, $paymentId);
                $stmt->execute();
                $conn->commit();
                $transactionStarted = false;
                $_SESSION['cust_name'] = $fullName;
                header('Location: confirmation.php?res_id=' . $resId);
                exit;
            }
            $conn->rollback();
            $transactionStarted = false;
        } catch (mysqli_sql_exception $e) {
            if ($transactionStarted) $conn->rollback();
            error_log('CineBook booking failure: ' . $e->getMessage());
            $errors[] = ((int)$e->getCode() === 1062)
                ? 'One of these seats was just reserved. Refresh and pick another seat.'
                : 'Booking could not be completed right now. Please try again.';
        }
    }
}

// Refresh availability AFTER processing a failed booking (including concurrent races).
// Occupancy is shown on the client for convenience. The POST handler
// rechecks it inside an exclusive showtime lock; the DB unique key is final protection.
$occupiedByMovie = [];
$occRes = $conn->query("SELECT Movie_ID, Seat_Code FROM reservation_seat");
while ($o = $occRes->fetch_assoc()) {
    $occupiedByMovie[(int)$o['Movie_ID']][] = $o['Seat_Code'];
}


// Group showtimes by film for the two-step movie → time pickers
$byTitle = [];
foreach ($moviesRows as $m) { $byTitle[$m['Title']][] = $m; }

// Which showtime is pre-selected (from ?movie_id, or a failed POST)
$selectedId = $preselectMovie;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['movie_id'])) {
    $selectedId = intval($_POST['movie_id']);
}
$preselectTitle = '';
foreach ($moviesRows as $m) {
    if ($m['Movie_ID'] == $selectedId) { $preselectTitle = $m['Title']; break; }
}

include 'includes/header.php';
?>

<?php $bookBanner = $bookMovie ? backdrop_style($bookMovie['Backdrop']) : ''; ?>
<?php if ($bookBanner !== ''): ?>
    <div class="detail-banner has-img" data-genre="<?php echo htmlspecialchars($bookMovie['Genre']); ?>"<?php echo $bookBanner; ?>>
        <span class="poster-title serif" style="font-size:1.7em;position:relative;z-index:2;">Now Booking &middot; <?php echo htmlspecialchars($bookMovie['Title']); ?></span>
    </div>
<?php else: ?>
    <img class="page-banner" src="images/ui/theatres-banner.png" alt="CineBook Cinemas - Book Tickets">
<?php endif; ?>

<h1 class="detail-title" style="font-size:1.9em;margin-bottom:4px;">Book Your Tickets</h1>
<p class="form-sub">Choose a showtime, pick your seats, and confirm your booking.</p>

<?php if (!empty($errors)): ?>
    <div class="error-msg">
        <ul style="margin-left:18px;">
            <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($bookMovie && $myAge < cert_min_age($bookMovie['Certificate'])): ?>
    <div class="error-msg">This film is rated <strong><?php echo htmlspecialchars($bookMovie['Certificate']); ?></strong> — you must be at least <?php echo cert_min_age($bookMovie['Certificate']); ?> to book it. Your account age is <?php echo $myAge; ?>.</div>
<?php endif; ?>

<form method="POST" action="booking.php" id="bookingForm" novalidate>
<?php echo csrf_field(); ?>
<div class="booking-layout">

    <!-- LEFT: customer + showtime details -->
    <div class="panel">
        <h3>Your Details</h3>
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" maxlength="100" required
                   value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? cb_post_text('full_name') : $prefillName); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="120" required
                   value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? cb_post_text('email') : $prefillEmail); ?>">
        </div>
        <div class="form-group">
            <label for="contact">Contact Number</label>
            <input type="tel" id="contact" name="contact" maxlength="15" pattern="[0-9]{7,15}" required
                   placeholder="e.g. 91234567" value="<?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? cb_post_text('contact') : $prefillContact); ?>">
            <div class="hint">7-15 digits, numbers only.</div>
        </div>
        <div class="form-group">
            <label for="movie_pick">Movie</label>
            <select id="movie_pick" required>
                <option value="">-- Select a movie --</option>
                <?php foreach ($byTitle as $title => $sts):
                    $sel = ($preselectTitle === $title) ? 'selected' : ''; ?>
                    <option value="<?php echo htmlspecialchars($title); ?>" <?php echo $sel; ?>>
                        <?php echo htmlspecialchars($title); ?> (<?php echo htmlspecialchars($sts[0]['Certificate']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="movie_id">Showtime</label>
            <select id="movie_id" name="movie_id" required>
                <option value="">-- Select a time --</option>
            </select>
        </div>
        <div class="form-group">
            <label for="payment_type">Payment Method <small>(demo only — no actual charge)</small></label>
            <select id="payment_type" name="payment_type" required>
                <option value="">-- Select payment method --</option>
                <?php while ($p = $paymentTypes->fetch_assoc()): ?>
                    <option value="<?php echo $p['Payment_Type_ID']; ?>" <?php echo (isset($_POST['payment_type']) && (string) $_POST['payment_type'] === (string)$p['Payment_Type_ID']) ? 'selected' : ''; ?>>
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

        <input type="hidden" id="seats" name="seats" value="<?php echo htmlspecialchars(cb_post_text('seats')); ?>">
        <input type="hidden" id="num_tickets" name="num_tickets" value="<?php echo htmlspecialchars(cb_post_text('num_tickets') ?: '0'); ?>">

        <button type="submit" class="btn btn-block" style="margin-top:18px;">Confirm Booking</button>
    </div>

</div>
</form>

<script>
/* ============================================================
   Interactive seat picker (pure JavaScript — no AJAX/JSON)
   The occupied-seat map is written directly into JS by PHP.
   ============================================================ */

// Encode all PHP values as real JSON (including unusual movie titles).
var occupiedSeats = <?php echo json_encode($occupiedByMovie, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
var TICKET_PRICE = <?php echo json_encode($TICKET_PRICE); ?>;
var USER_AGE = <?php echo (int)$myAge; ?>;
var movieMinAge = <?php
    $minAgeMap = [];
    foreach ($moviesRows as $m) $minAgeMap[$m['Movie_ID']] = ['min' => cert_min_age($m['Certificate']), 'cert' => $m['Certificate']];
    echo json_encode($minAgeMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>;

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
var moviePick = document.getElementById("movie_pick");
var PRESELECT_ID = <?php echo (int) $selectedId; ?>;

// Showtimes grouped by film title (escaped JSON, not quoted JavaScript).
var showtimesByTitle = <?php
    $timesJson = [];
    foreach ($byTitle as $title => $sts) {
        $timesJson[$title] = [];
        foreach ($sts as $entry) {
            $timesJson[$title][] = [
                'id' => (int) $entry['Movie_ID'],
                'label' => date('d M Y', strtotime($entry['Date'])) . ', ' . date('g:i A', strtotime($entry['Showtime']))
            ];
        }
    }
    echo json_encode($timesJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>;

// Fill the Showtime dropdown based on the chosen Movie
function populateTimes(selectId) {
    var times = showtimesByTitle[moviePick.value] || [];
    movieSelect.innerHTML = '<option value="">-- Select a time --</option>';
    times.forEach(function (t) {
        var o = document.createElement("option");
        o.value = t.id;
        o.textContent = t.label;
        if (selectId && String(t.id) === String(selectId)) o.selected = true;
        movieSelect.appendChild(o);
    });
}
moviePick.addEventListener("change", function () {
    populateTimes(null);
    selected = [];
    buildGrid();
    updateSummary();
});

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
            showErrorBox("You can select a maximum of " + MAX_SEATS + " seats.", "Too many seats");
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

    sumMovie.textContent = moviePick.value ? moviePick.value : "—";
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
    var ar = movieMinAge[movieSelect.value];
    if (ar && USER_AGE < ar.min) {
        hintEl.textContent = "⚠ Rated " + ar.cert + " — you must be at least " + ar.min + " to book this film.";
    }
});

// Restore any seats kept after a failed server-side validation
(function restore() {
    // Populate showtimes for a pre-selected movie (from details page or failed POST)
    if (moviePick.value) populateTimes(PRESELECT_ID);

    var prev = seatsInput.value.trim();
    if (prev) {
        selected = [...new Set(prev.split(/[\s,]+/).map(function (s) { return s.toUpperCase(); })
            .filter(function (s) { return /^[A-H](10|[1-9])$/.test(s) && currentOccupied().indexOf(s) === -1; }))].slice(0, MAX_SEATS);
    }
    buildGrid();
    updateSummary();
})();

// ---- Submit validation (client-side, before the form is sent) ----
document.getElementById("bookingForm").addEventListener("submit", function (e) {
    var contact = document.getElementById("contact").value.trim();
    var fullName = document.getElementById("full_name").value.trim();
    var email = document.getElementById("email").value.trim();
    var msg = "";

    if (!fullName || fullName.length > 100) msg += "Full name must be 1–100 characters.\n";
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email) || email.length > 120) msg += "Enter a valid email (maximum 120 characters).\n";
    if (!document.getElementById("payment_type").value) msg += "Please select a payment method.\n";
    if (!moviePick.value) msg += "Please select a movie.\n";
    else if (!movieSelect.value) msg += "Please select a showtime.\n";
    if (!/^[0-9]{7,15}$/.test(contact)) msg += "Contact number must be 7-15 digits.\n";
    if (selected.length < 1) msg += "Please select at least one seat.\n";
    if (selected.length > MAX_SEATS) msg += "You can book a maximum of " + MAX_SEATS + " seats.\n";
    var ar = movieMinAge[movieSelect.value];
    if (ar && USER_AGE < ar.min) msg += "You must be at least " + ar.min + " to book this " + ar.cert + "-rated film (your age: " + USER_AGE + ").\n";

    if (msg !== "") {
        showErrorBox(msg);
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
