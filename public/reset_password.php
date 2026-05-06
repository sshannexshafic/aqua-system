<?php
session_start();

// Protect the page
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

require_once '../includes/db_connection.php';
$message = '';
$error = '';

if ($_POST && isset($_POST['reset_password'])) {
    $new_pass = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($new_pass === $confirm && strlen($new_pass) >= 6) {
        $db = (new Database())->getConnection();
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashed, $_SESSION['reset_user_id']])) {
            
            // Clean session
            session_unset();
            session_destroy();

            $message = "
                <strong>✅ Password successfully reset!</strong><br><br>
                <a href='login.php' class='btn-primary' style='display:inline-block; padding:12px 25px; text-decoration:none;'>
                    Click here to go to Login Page
                </a>
            ";
            
        } else {
            $error = "Failed to update password. Please try again.";
        }
    } else {
        $error = "Passwords do not match or password is too short (minimum 6 characters).";
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="login-page">
    <div class="login-container">
        <div class="login-card">
            <h2>Set New Password</h2>

            <?php if ($message): ?>
                <div class="success" style="text-align:center; font-size:1.1rem;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Show form only if reset not successful -->
            <?php if (!$message): ?>
            <form method="POST">
                <div class="input-group">
                    <input type="password" name="new_password" placeholder="New Password" required minlength="6">
                </div>
                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
                </div>
                <button type="submit" name="reset_password" class="btn-primary full-width">
                    Update Password
                </button>
            </form>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>