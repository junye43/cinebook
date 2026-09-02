<?php
$pageTitle = "Login";
require_once 'includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT Cust_ID, Cust_Name, Password FROM customer WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['Password'])) {
            $_SESSION['cust_id'] = $user['Cust_ID'];
            $_SESSION['cust_name'] = $user['Cust_Name'];
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Incorrect email or password.";
        }
    } else {
        $errors[] = "Incorrect email or password.";
    }
}

include 'includes/header.php';
?>

<div class="form-panel">
    <h2>Login</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-msg">
            <?php foreach ($errors as $e) echo "<p>" . htmlspecialchars($e) . "</p>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
    <p style="margin-top:16px;color:var(--text-muted);">No account yet? <a href="register.php" style="color:var(--gold);">Register here</a>.</p>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
