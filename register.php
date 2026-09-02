<?php
$pageTitle = "Register";
require_once 'includes/db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname   = trim($_POST['fname'] ?? '');
    $lname   = trim($_POST['lname'] ?? '');
    $age     = intval($_POST['age'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $number  = trim($_POST['number'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fname === '' || $lname === '') $errors[] = "First and last name are required.";
    if ($age < 1 || $age > 120) $errors[] = "Please enter a valid age.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";

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
            $success = true;
        }
    }
}

include 'includes/header.php';
?>

<div class="form-panel">
    <h2>Create an Account</h2>

    <?php if ($success): ?>
        <div class="success-msg">Registration successful! You can now <a href="login.php">log in</a>.</div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="error-msg">
                <ul style="margin-left:18px;">
                    <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
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
                    <input type="number" id="age" name="age" min="1" max="120" required value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
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
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="6" required>
                <div class="hint">At least 6 characters.</div>
            </div>
            <button type="submit" class="btn">Register</button>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
