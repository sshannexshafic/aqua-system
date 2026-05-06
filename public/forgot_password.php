<?php
// FIXED - No duplicate session_start()
require_once '../includes/auth.php';     // This safely starts the session

require_once '../config/database.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$step = $_GET['step'] ?? 'request';
$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSIONS
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pdo = DatabaseConfig::getConnection();

    // STEP 1: Request Reset Code
    if ($step === 'request') {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address";
        } else {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = random_int(100000, 999999);
                $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $stmt = $pdo->prepare("
                    INSERT INTO password_reset_tokens (email, token, expires_at, used)
                    VALUES (?, ?, ?, 0)
                    ON DUPLICATE KEY UPDATE token=?, expires_at=?, used=0
                ");
                $stmt->execute([$email, $token, $expires, $token, $expires]);

                if (sendEmail($email, $user['username'], $token)) {
                    $_SESSION['reset_email'] = $email;
                    $step = 'verify';
                    $success = "A reset code has been sent to your email.";
                } else {
                    $error = "Failed to send email. Please try again later.";
                }
            } else {
                $error = "No account found with this email.";
            }
        }
    }

    // STEP 2: Verify Code
    elseif ($step === 'verify') {
        $token = trim($_POST['token'] ?? '');

        if (!isset($_SESSION['reset_email'])) {
            $error = "Session expired. Please start over.";
            $step = 'request';
        } else {
            $stmt = $pdo->prepare("
                SELECT email FROM password_reset_tokens 
                WHERE email = ? AND token = ? AND expires_at > NOW() AND used = 0
            ");
            $stmt->execute([$_SESSION['reset_email'], $token]);
            $row = $stmt->fetch();

            if ($row) {
                $_SESSION['reset_token'] = $token;
                $step = 'reset';
                $success = "Code verified successfully. Please set your new password.";
            } else {
                $error = "Invalid or expired code. Please try again.";
            }
        }
    }

    // STEP 3: Reset Password
    elseif ($step === 'reset') {
        if (!isset($_SESSION['reset_email'])) {
            $error = "Session expired. Please start over.";
            $step = 'request';
        } else {
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (strlen($password) < 6) {
                $error = "Password must be at least 6 characters.";
            } elseif ($password !== $confirm) {
                $error = "Passwords do not match.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed, $_SESSION['reset_email']]);

                $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE email = ?");
                $stmt->execute([$_SESSION['reset_email']]);

                unset($_SESSION['reset_email'], $_SESSION['reset_token']);

                $success = "✅ Password successfully reset!<br><br>
                            <a href='login.php' class='btn-primary'>Click here to Login</a>";
                $step = 'request';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| EMAIL FUNCTION
|--------------------------------------------------------------------------
*/
function sendEmail($to, $username, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ssewanyanashafic266@gmail.com';
        $mail->Password   = 'bpce sfpo bhbf dpbh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('ssewanyanashafic266@gmail.com', 'Mugwe Fish Pond');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code';
        $mail->Body    = "
            <h2>Hello {$username},</h2>
            <p>Your password reset code is:</p>
            <h1 style='color:#3b82f6;'>{$token}</h1>
            <p>This code expires in 15 minutes.</p>
        ";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="login-page">
    <div class="login-container">
        <div class="login-card">
            <h2>Password Reset</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?= $success ?></div>
            <?php endif; ?>

            <!-- Step 1: Request -->
            <?php if ($step === 'request'): ?>
                <form method="POST" action="?step=request">
                    <div class="input-group">
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                    <button type="submit" class="btn-primary full-width">Send Reset Code</button>
                </form>
            <?php endif; ?>

            <!-- Step 2: Verify -->
            <?php if ($step === 'verify'): ?>
                <form method="POST" action="?step=verify">
                    <div class="input-group">
                        <input type="text" name="token" maxlength="6" placeholder="Enter 6-digit code" required style="text-align:center;font-size:1.5rem;">
                    </div>
                    <button type="submit" class="btn-primary full-width">Verify Code</button>
                </form>
            <?php endif; ?>

            <!-- Step 3: Reset Password -->
            <?php if ($step === 'reset'): ?>
                <form method="POST" action="?step=reset">
                    <div class="input-group">
                        <input type="password" name="password" placeholder="New Password" required minlength="6">
                    </div>
                    <div class="input-group">
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
                    </div>
                    <button type="submit" class="btn-primary full-width">Reset Password</button>
                </form>
            <?php endif; ?>

            <div style="text-align:center;margin-top:1rem;">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>