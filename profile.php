<?php
$pageTitle = "My Profile";
require_once 'includes/db_connect.php';

require_login('profile.php');
$custId = current_user_id();

$errors = [];
$success = "";

// ---- Load the current customer record ----
$stmt = $conn->prepare("SELECT Cust_Name, Cust_Lname, Cust_Age, Cust_Address, Cust_Number, Email
                        FROM customer WHERE Cust_ID = ?");
$stmt->bind_param("i", $custId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) { // safety: session points to a missing customer
    session_unset();
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) $errors[] = "Your session expired. Please try again.";

    $fname   = trim($_POST['fname'] ?? '');
    $lname   = trim($_POST['lname'] ?? '');
    $age     = intval($_POST['age'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $number  = trim($_POST['number'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // ---- Validation ----
    if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,49}$/", $fname)) $errors[] = "Please enter a valid first name.";
    if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,49}$/", $lname)) $errors[] = "Please enter a valid last name.";
    if ($age < 12 || $age > 120) $errors[] = "Age must be between 12 and 120.";
    if (!preg_match('/^[0-9]{7,15}$/', $number)) $errors[] = "Contact number must be 7-15 digits.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";

    $changePassword = ($newPass !== '' || $confirm !== '');
    if ($changePassword) {
        if (strlen($newPass) < 6) $errors[] = "New password must be at least 6 characters.";
        if ($newPass !== $confirm) $errors[] = "New passwords do not match.";
    }

    // Email must be unique to another account
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT Cust_ID FROM customer WHERE Email = ? AND Cust_ID <> ?");
        $stmt->bind_param("si", $email, $custId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = "That email is already used by another account.";
    }

    if (empty($errors)) {
        if ($changePassword) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE customer
                SET Cust_Name=?, Cust_Lname=?, Cust_Age=?, Cust_Address=?, Cust_Number=?, Email=?, Password=?
                WHERE Cust_ID=?");
            $stmt->bind_param("ssissssi", $fname, $lname, $age, $address, $number, $email, $hashed, $custId);
        } else {
            $stmt = $conn->prepare("UPDATE customer
                SET Cust_Name=?, Cust_Lname=?, Cust_Age=?, Cust_Address=?, Cust_Number=?, Email=?
                WHERE Cust_ID=?");
            $stmt->bind_param("ssisssi", $fname, $lname, $age, $address, $number, $email, $custId);
        }
        $stmt->execute();

        $_SESSION['cust_name'] = $fname;
        $success = "Your profile has been updated." . ($changePassword ? " Password changed." : "");

        // refresh displayed values
        $user = ['Cust_Name'=>$fname, 'Cust_Lname'=>$lname, 'Cust_Age'=>$age,
                 'Cust_Address'=>$address, 'Cust_Number'=>$number, 'Email'=>$email];
    }
}

include 'includes/header.php';

// helper: value to show (prefer submitted value on error, else stored)
function pv($postKey, $stored) {
    return htmlspecialchars($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST[$postKey] ?? '') : $stored);
}
?>

<div class="form-panel">
    <img class="auth-banner" src="images/ui/theatres-banner.png" alt="CineBook Cinemas">
    <h2>My Profile</h2>
    <p class="form-sub">Update your account details below.</p>

    <?php if ($success): ?>
        <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="error-msg">
            <ul style="margin-left:18px;">
                <?php foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="profile.php" id="profileForm">
        <?php echo csrf_field(); ?>
        <div class="form-row">
            <div class="form-group">
                <label for="fname">First Name</label>
                <input type="text" id="fname" name="fname" required value="<?php echo pv('fname', $user['Cust_Name']); ?>">
            </div>
            <div class="form-group">
                <label for="lname">Last Name</label>
                <input type="text" id="lname" name="lname" required value="<?php echo pv('lname', $user['Cust_Lname']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" min="12" max="120" required value="<?php echo pv('age', $user['Cust_Age']); ?>">
            </div>
            <div class="form-group">
                <label for="number">Contact Number</label>
                <input type="tel" id="number" name="number" pattern="[0-9]{7,15}" required value="<?php echo pv('number', $user['Cust_Number']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?php echo pv('address', $user['Cust_Address']); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?php echo pv('email', $user['Email']); ?>">
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:8px 0 18px;">
        <p class="form-sub" style="margin-bottom:14px;">Change password (leave blank to keep your current password)</p>
        <div class="form-row">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" minlength="6">
                <div class="hint">At least 6 characters.</div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6">
            </div>
        </div>

        <button type="submit" class="btn btn-block">Save Changes</button>
    </form>
</div>

<script>
document.getElementById('profileForm').addEventListener('submit', function (e) {
    var msg = "";
    var name = /^[A-Za-z][A-Za-z '\-]{1,49}$/;
    if (!name.test(document.getElementById('fname').value.trim())) msg += "Enter a valid first name.\n";
    if (!name.test(document.getElementById('lname').value.trim())) msg += "Enter a valid last name.\n";
    var age = parseInt(document.getElementById('age').value, 10);
    if (isNaN(age) || age < 12 || age > 120) msg += "Age must be between 12 and 120.\n";
    if (!/^[0-9]{7,15}$/.test(document.getElementById('number').value)) msg += "Contact number must be 7-15 digits.\n";
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(document.getElementById('email').value.trim())) msg += "Enter a valid email.\n";
    var np = document.getElementById('new_password').value;
    var cp = document.getElementById('confirm_password').value;
    if (np !== "" || cp !== "") {
        if (np.length < 6) msg += "New password must be at least 6 characters.\n";
        if (np !== cp) msg += "New passwords do not match.\n";
    }
    if (msg !== "") { alert(msg); e.preventDefault(); }
});
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
