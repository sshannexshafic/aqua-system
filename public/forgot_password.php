<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../includes/functions.php';
require_once '../config/database.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

$step = $_GET['step'] ?? 'request';
$token = $_GET['token'] ?? '';
$email = $_POST['email'] ?? '';
$error = '';
$success = '';

// Step 1: Request token
if ($_POST && $step == 'request') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = sprintf("%06d", mt_rand(0, 999999));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $stmt = $pdo->prepare("
                INSERT INTO password_reset_tokens (email, token, expires_at, used) 
                VALUES (?, ?, ?, 0) 
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
            ");
            $stmt->execute([$email, $token, $expires, $token, $expires]);
            
            $subject = "Mugwe Fish Pond - Password Reset Code";
            $message = "
                <h2>Password Reset Request</h2>
                <p>Hello {$user['username']},</p>
                <p>Your password reset code is:</p>
                <div style='font-size: 3rem; font-weight: bold; color: #10b981; text-align: center; padding: 2rem; background: #f0fdf4; border-radius: 12px; letter-spacing: 0.5rem;'>$token</div>
                <p>This code expires in <strong>15 minutes</strong>.</p>
                <p>If you didn't request this, ignore this email.</p>
                <hr>
                <small>Mugwe Fish Pond AMS - Busolwe, Butaleja</small>
            ";
            
            if (sendEmail($email, $subject, $message)) {
                $success = 'Reset code sent to your email! Check your inbox (and spam).';
                $_SESSION['reset_email'] = $email;
                $step = 'verify'; // move user to next step
            } else {
                $error = 'Failed to send email. Try again or contact admin.';
            }
        } else {
            $error = 'Email not found in our records.';
        }
    }
}

// Step 2: Verify token
if ($_POST && $step == 'verify') {
    $token = $_POST['token'];
    $pdo = DatabaseConfig::getConnection();
    
    $stmt = $pdo->prepare("
        SELECT email FROM password_reset_tokens 
        WHERE token = ? AND expires_at > NOW() AND used = 0
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if ($reset) {
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_email'] = $reset['email'];
        $step = 'reset';
    } else {
        $error = 'Invalid or expired token!';
    }
}

// Step 3: Reset password
if ($_POST && $step == 'reset') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $pdo = DatabaseConfig::getConnection();
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    if ($stmt->execute([$password, $_SESSION['reset_email']])) {
        
        $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        $stmt->execute([$_SESSION['reset_token']]);
        
        $success = 'Password reset successfully! You can now login.';
        unset($_SESSION['reset_email'], $_SESSION['reset_token']);
    } else {
        $error = 'Failed to reset password.';
    }
}

// ✅ REAL EMAIL FUNCTION USING PHPMailer
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ssewanyanashafic266@gmail.com';
        $mail->Password   = 'yhkw focz vsvs bhpa'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('ssewanyanashafic266@gmail.com', 'Mugwe Fish Pond');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}
?>