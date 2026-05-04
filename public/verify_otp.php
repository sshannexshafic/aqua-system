<?php
session_start();
if (!isset($_SESSION['reset_user_id']) || time() > $_SESSION['reset_expiry']) {
    header("Location: forgot_password.php");
    exit();
}

$message = '';
$error = '';

if ($_POST && isset($_POST['verify_otp'])) {
    if ($_POST['otp'] == $_SESSION['reset_otp']) {
        $_SESSION['otp_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid or expired code.";
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="login-page">
    <div class="login-container">
        <div class="login-card">
            <h2>Enter Verification Code</h2>
            <p>Check your Email for the 6-digit code</p>

            <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <input type="text" name="otp" maxlength="6" placeholder="123456" required style="text-align:center;font-size:1.5rem;letter-spacing:8px;">
                </div>
                <button type="submit" name="verify_otp" class="btn-primary full-width">Verify Code</button>
            </form>

            <a href="forgot_password.php" style="display:block;margin-top:1rem;text-align:center;">Resend Code</a>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>