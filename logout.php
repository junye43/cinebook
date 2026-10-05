<?php
$pageTitle = "Log Out";
require_once 'includes/db_connect.php';   // starts session + helpers

// Not logged in? Nothing to confirm.
if (!is_logged_in()) { header("Location: index.php"); exit; }

// Confirmed logout (POST with valid CSRF token)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

include 'includes/header.php';
?>

<div class="confirm-wrap" style="max-width:480px;">
    <div class="form-panel" style="text-align:center;">
        <h2>Log out?</h2>
        <p class="form-sub">Are you sure you want to log out of your CineBook account?</p>
        <form method="POST" action="logout.php" style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:6px;">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn">Yes, log out</button>
            <a href="index.php" class="btn btn-ghost">Cancel</a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; $conn->close(); ?>
