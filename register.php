<?php
$pageTitle = "Register";
require_once 'includes/db_connect.php';

$errors = [];
$redirect = safe_local_redirect($_POST['redirect'] ?? $_GET['redirect'] ?? '', 'index.php');

if (is_logged_in()) { header("Location: $redirect"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) $errors[] = "Your session expired. Please try again.";

    $fname    = trim($_POST['fname'] ?? '');
    $lname    = trim($_POST['lname'] ?? '');
    $age      = intval($_POST['age'] ?? 0);
    $address  = trim($_POST['address'] ?? '');
    $number   = trim($_POST['number'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ---- Server-side validation ----
    if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,49}$/", $fname)) $errors[] = "Please enter a valid first name.";
    if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,49}$/", $lname)) $errors[] = "Please enter a valid last name.";
    if ($age < 12 || $age > 120) $errors[] = "Age must be between 12 and 120.";
    if (!preg_match('/^[0-9]{7,15}$/', $number)) $errors[] = "Contact number must be 7-15 digits.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT Cust_ID FROM customer WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customer (Cust_Name, Cust_Lname, Cust_Age, Cust_Address, Cust_Number, Email, Password)
                                     VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissss", $fname, $lname, $age, $address, $number, $email, $hashed);
            $stmt->execute();

            // Auto-login the new customer, then continue to their destination.
            session_regenerate_id(true);
            $_SESSION['cust_id'] = $conn->insert_id;
            $_SESSION['cust_name'] = $fname;
            header("Location: $redirect");
            exit;
        }
    }
}

include 'includes/header.php';
?>

<div class="form-panel">
    <h2>Create an Account</h2>
    <p class="form-sub">Join CineBook to book faster and track your reservations.</p>

    <?php if (!empty($errors)): ?>
        <div class="error-msg">
            <ul style="margin-left:18px;">
                <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="registerForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="fname">First Name</label>
                <input type="text" id="fname" name="fname" required value="<?php echo htmlspecialchars($_POST['fname'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="lname">Last Name</label>
                <input type="text" id="lname" name="lname" required value="<?php echo htmlspecialchars($_POST['lname'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" min="12" max="120" required value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="number">Contact Number</label>
                <input type="tel" id="number" name="number" pattern="[0-9]{7,15}" required value="<?php echo htmlspecialchars($_POST['number'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" required>
                <div class="hint">At least 6 characters.</div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
            </div>
        </div>
        <button type="submit" class="btn btn-block">Register</button>
    </form>
    <p class="form-footer-note">Already have an account?
        <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($redirect) : ''; ?>">Log in</a>.
    </p>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', function (e) {
    var msg = "";
    var name = /^[A-Za-z][A-Za-z '\-]{1,49}$/;
    if (!name.test(document.getElementById('fname').value.trim())) msg += "Enter a valid first name.\n";
    if (!name.test(document.getElementById('lname').value.trim())) msg += "Enter a valid last name.\n";
    var age = parseInt(document.getElementById('age').value, 10);
    if (isNaN(age) || age < 12 || age > 120) msg += "Age must be between 12 and 120.\n";
    if (!/^[0-9]{7,15}$/.test(document.getElementById('number').value)) msg += "Contact number must be 7-15 digits.\n";
    var email = document.getElementById('email').value.trim();
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) msg += "Enter a valid email.\n";
    var pw = document.getElementById('password').value;
    if (pw.length < 6) msg += "Password must be at least 6 characters.\n";
    if (pw !== document.getElementById('confirm_password').value) msg += "Passwords do not match.\n";
    if (msg !== "") { alert(msg); e.preventDefault(); }
});
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
