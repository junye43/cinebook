<?php
$pageTitle = "Register";
require_once 'includes/db_connect.php';

$redirect = safe_local_redirect($_POST['redirect'] ?? $_GET['redirect'] ?? '', 'index.php');
if (is_logged_in()) { header("Location: $redirect"); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) $errors[] = "Your session expired. Please try again.";

    $fname    = is_string($_POST['fname'] ?? null) ? trim($_POST['fname']) : ''; 
    $lname    = is_string($_POST['lname'] ?? null) ? trim($_POST['lname']) : ''; 
    $dob      = is_string($_POST['dob'] ?? null) ? trim($_POST['dob']) : ''; 
    $age      = age_from_dob($dob);
    $address  = is_string($_POST['address'] ?? null) ? trim($_POST['address']) : ''; 
    $number   = is_string($_POST['number'] ?? null) ? trim($_POST['number']) : ''; 
    $email    = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : ''; 
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : ''; 
    $confirm  = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : ''; 

    if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,49}$/", $fname)) $errors[] = "Please enter a valid first name.";
    if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,49}$/", $lname)) $errors[] = "Please enter a valid last name.";
    if ($age === null) $errors[] = "Please enter a valid date of birth.";
    elseif ($age < 12 || $age > 120) $errors[] = "You must be between 12 and 120 years old.";
    if (!preg_match('/^[0-9]{7,15}$/', $number)) $errors[] = "Contact number must be 7-15 digits.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (strlen($address) > 255) $errors[] = 'Address must be 255 characters or fewer.';
    if (strlen($email) > 120) $errors[] = 'Email must be 120 characters or fewer.';
    if (strlen($password) < 8 || strlen($password) > 72) $errors[] = 'Password must be between 8 and 72 characters.';
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        try {
        $stmt = $conn->prepare("SELECT Cust_ID FROM customer WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customer (Cust_Name, Cust_Lname, Cust_Age, Cust_DOB, Cust_Address, Cust_Number, Email, Password)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisssss", $fname, $lname, $age, $dob, $address, $number, $email, $hashed);
            $stmt->execute();

            session_regenerate_id(true);
            $_SESSION['cust_id'] = $conn->insert_id;
            $_SESSION['cust_name'] = $fname;
            header("Location: $redirect");
            exit;
        }
        } catch (mysqli_sql_exception $e) {
            error_log('CineBook registration failed: ' . $e->getMessage());
            $errors[] = (int)$e->getCode() === 1062
                ? 'An account with this email already exists.'
                : 'Registration is temporarily unavailable. Please try again.';
        }
    }
}

include 'includes/header.php';
$loginLink = 'login.php' . (isset($_GET['redirect']) ? '?redirect=' . urlencode($redirect) : '');
?>

<div class="form-panel">
    <img class="auth-banner" src="images/ui/theatres-banner.png" alt="CineBook Cinemas">
    <h2>Create an account</h2>
    <p class="form-sub">Join CineBook to book faster and track your reservations.</p>

    <?php if (!empty($errors)): ?>
        <div class="error-msg">
            <ul style="margin-left:18px;">
                <?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="registerForm" novalidate>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="fname">First Name</label>
                <input type="text" id="fname" name="fname" maxlength="50" required value="<?php echo posted_value('fname'); ?>">
            </div>
            <div class="form-group">
                <label for="lname">Last Name</label>
                <input type="text" id="lname" name="lname" maxlength="50" required value="<?php echo posted_value('lname'); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" max="<?php echo date('Y-m-d'); ?>" required value="<?php echo posted_value('dob'); ?>">
            </div>
            <div class="form-group">
                <label for="number">Contact Number</label>
                <input type="tel" id="number" name="number" maxlength="15" pattern="[0-9]{7,15}" required value="<?php echo posted_value('number'); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" maxlength="255" value="<?php echo posted_value('address'); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="120" required value="<?php echo posted_value('email'); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="8" maxlength="72" required>
                <div class="hint">8–72 characters.</div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="72" required>
            </div>
        </div>
        <button type="submit" class="btn btn-block">Register</button>
    </form>

    <p class="form-footer-note">Already a member? <a href="<?php echo htmlspecialchars($loginLink); ?>">Log in</a>.</p>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function (e) {
    var msg = "";
    var name = /^[A-Za-z][A-Za-z '\-]{1,49}$/;
    if (!name.test(document.getElementById('fname').value.trim())) msg += "Enter a valid first name.\n";
    if (!name.test(document.getElementById('lname').value.trim())) msg += "Enter a valid last name.\n";
    var dob = document.getElementById('dob').value;
    if (!dob) { msg += "Please enter your date of birth.\n"; }
    else {
        var birth = new Date(dob + 'T12:00:00');
        var today = new Date();
        if (isNaN(birth.getTime()) || birth.getFullYear() + '-' + String(birth.getMonth()+1).padStart(2,'0') + '-' + String(birth.getDate()).padStart(2,'0') !== dob || birth > today) {
            msg += "Please enter a valid date of birth.\n";
        } else {
            var age = today.getFullYear() - birth.getFullYear();
            if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) age--;
            if (age < 12 || age > 120) msg += "You must be between 12 and 120 years old.\n";
        }
    }
    if (!/^[0-9]{7,15}$/.test(document.getElementById('number').value)) msg += "Contact number must be 7-15 digits.\n";
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(document.getElementById('email').value.trim())) msg += "Enter a valid email.\n";
    if (document.getElementById('address').value.trim().length > 255) msg += "Address must be 255 characters or fewer.\n";
    if (document.getElementById('email').value.trim().length > 120) msg += "Email must be 120 characters or fewer.\n";
    var pw = document.getElementById('password').value;
    if (pw.length < 8 || pw.length > 72) msg += "Password must be between 8 and 72 characters.\n";
    if (pw !== document.getElementById('confirm_password').value) msg += "Passwords do not match.\n";
    if (msg !== "") { showErrorBox(msg); e.preventDefault(); }
});
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
