<?php
$pageTitle = "Login";
require_once 'includes/db_connect.php';

$errors = [];
// Where to send the user after a successful login (validated to a local page).
$redirect = safe_local_redirect($_POST['redirect'] ?? $_GET['redirect'] ?? '', 'index.php');

// Already logged in? Go straight there.
if (is_logged_in()) { header("Location: $redirect"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) $errors[] = "Your session expired. Please try again.";

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT Cust_ID, Cust_Name, Password FROM customer WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['Password'])) {
                session_regenerate_id(true);              // prevent session fixation
                $_SESSION['cust_id'] = $user['Cust_ID'];
                $_SESSION['cust_name'] = $user['Cust_Name'];
                header("Location: $redirect");
                exit;
            }
        }
        // Same message whether the email or password is wrong (no user enumeration)
        $errors[] = "Incorrect email or password.";
    }
}

include 'includes/header.php';
?>

<div class="form-panel">
    <h2>Welcome back</h2>
    <p class="form-sub">Log in to book tickets and manage your reservations.</p>

    <?php if (!empty($errors)): ?>
        <div class="error-msg">
            <?php foreach ($errors as $e) echo "<p>" . htmlspecialchars($e) . "</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['redirect']) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <div class="success-msg">Please log in to continue to your booking.</div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-block">Login</button>
    </form>
    <p class="form-footer-note">No account yet?
        <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($redirect) : ''; ?>">Register here</a>.
    </p>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function (e) {
    var email = document.getElementById('email').value.trim();
    var pw = document.getElementById('password').value;
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email) || pw.length < 1) {
        alert('Please enter a valid email and your password.');
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
