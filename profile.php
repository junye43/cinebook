<?php
$pageTitle = "My Profile";
require_once 'includes/db_connect.php';

require_login('profile.php');
$custId = current_user_id();

$errors = [];
$success = "";

// ---- Load the current customer record ----
$stmt = $conn->prepare("SELECT Cust_Name, Cust_Lname, Cust_Age, Cust_DOB, Cust_Address, Cust_Number, Email, Password
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

    $fname   = is_string($_POST['fname'] ?? null) ? trim($_POST['fname']) : '';
    $lname   = is_string($_POST['lname'] ?? null) ? trim($_POST['lname']) : '';
    // Date of birth is fixed at registration and cannot be changed here.
    $address = is_string($_POST['address'] ?? null) ? trim($_POST['address']) : '';
    $number  = is_string($_POST['number'] ?? null) ? trim($_POST['number']) : '';
    $email   = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $newPass = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $currentPass = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';

    // ---- Validation ----
    if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,49}$/", $fname)) $errors[] = "Please enter a valid first name.";
    if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,49}$/", $lname)) $errors[] = "Please enter a valid last name.";
    if (!preg_match('/^[0-9]{7,15}$/', $number)) $errors[] = "Contact number must be 7-15 digits.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";

    if (strlen($address) > 255) $errors[] = 'Address must be 255 characters or fewer.';
    if (strlen($email) > 120) $errors[] = 'Email must be 120 characters or fewer.';
    $changePassword = ($newPass !== '' || $confirm !== '' || $currentPass !== '');
    if ($changePassword) {
        if (strlen($newPass) < 8 || strlen($newPass) > 72) $errors[] = 'New password must be between 8 and 72 characters.';
        if (!password_verify($currentPass, $user['Password'])) $errors[] = 'Current password is incorrect.';
        if ($newPass !== $confirm) $errors[] = "New passwords do not match.";
    }

    try {
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
                SET Cust_Name=?, Cust_Lname=?, Cust_Address=?, Cust_Number=?, Email=?, Password=?
                WHERE Cust_ID=?");
            $stmt->bind_param("ssssssi", $fname, $lname, $address, $number, $email, $hashed, $custId);
        } else {
            $stmt = $conn->prepare("UPDATE customer
                SET Cust_Name=?, Cust_Lname=?, Cust_Address=?, Cust_Number=?, Email=?
                WHERE Cust_ID=?");
            $stmt->bind_param("sssssi", $fname, $lname, $address, $number, $email, $custId);
        }
        $stmt->execute();

        $_SESSION['cust_name'] = $fname;
        $success = "Your profile has been updated." . ($changePassword ? " Password changed." : "");

        // refresh displayed values (DOB is unchanged)
        $user['Cust_Name'] = $fname; $user['Cust_Lname'] = $lname;
        $user['Cust_Address'] = $address; $user['Cust_Number'] = $number; $user['Email'] = $email;
    }
    } catch (mysqli_sql_exception $e) {
        error_log('CineBook profile update failed: ' . $e->getMessage());
        $errors[] = (int)$e->getCode() === 1062
            ? 'That email is already used by another account.'
            : 'Profile could not be saved right now. Please try again.';
    }
}

include 'includes/header.php';

// helper: value to show (prefer submitted value on error, else stored)
function pv($postKey, $stored) {
    return $_SERVER['REQUEST_METHOD'] === 'POST' ? posted_value($postKey) : htmlspecialchars((string)$stored, ENT_QUOTES, 'UTF-8');
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

    <form method="POST" action="profile.php" id="profileForm" novalidate>
        <?php echo csrf_field(); ?>
        <div class="form-row">
            <div class="form-group">
                <label for="fname">First Name</label>
                <input type="text" id="fname" name="fname" maxlength="50" required value="<?php echo pv('fname', $user['Cust_Name']); ?>">
            </div>
            <div class="form-group">
                <label for="lname">Last Name</label>
                <input type="text" id="lname" name="lname" maxlength="50" required value="<?php echo pv('lname', $user['Cust_Lname']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" value="<?php echo htmlspecialchars($user['Cust_DOB']); ?>" disabled>
                <div class="hint">Your date of birth cannot be changed.</div>
            </div>
            <div class="form-group">
                <label for="number">Contact Number</label>
                <input type="tel" id="number" name="number" maxlength="15" pattern="[0-9]{7,15}" required value="<?php echo pv('number', $user['Cust_Number']); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" maxlength="255" value="<?php echo pv('address', $user['Cust_Address']); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="120" required value="<?php echo pv('email', $user['Email']); ?>">
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:8px 0 18px;">
        <p class="form-sub" style="margin-bottom:14px;">Change password (leave blank to keep your current password)</p>
        <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                <div class="hint">Required only when changing your password.</div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" minlength="8" maxlength="72">
                <div class="hint">8–72 characters.</div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="72">
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
    if (!/^[0-9]{7,15}$/.test(document.getElementById('number').value)) msg += "Contact number must be 7-15 digits.\n";
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(document.getElementById('email').value.trim())) msg += "Enter a valid email.\n";
    if (document.getElementById('address').value.trim().length > 255) msg += "Address must be 255 characters or fewer.\n";
    if (document.getElementById('email').value.trim().length > 120) msg += "Email must be 120 characters or fewer.\n";
    var old = document.getElementById('current_password').value;
    var np = document.getElementById('new_password').value;
    var cp = document.getElementById('confirm_password').value;
    if (np !== "" || cp !== "" || old !== "") {
        if (!old) msg += "Enter your current password.\n";
        if (np.length < 8 || np.length > 72) msg += "New password must be between 8 and 72 characters.\n";
        if (np !== cp) msg += "New passwords do not match.\n";
    }
    if (msg !== "") { showErrorBox(msg); e.preventDefault(); }
});
</script>

<?php include 'includes/footer.php'; $conn->close(); ?>
