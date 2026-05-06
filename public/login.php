<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') header("Location: ../admin/dashboard.php");
    elseif ($role === 'vet') header("Location: ../vet/dashboard.php");
    else header("Location: ../farmer/dashboard.php");
    exit();
}

$error = '';
if ($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (login($username, $password)) {
        // Log successful login (non-fatal — don't let logging break login)
        try { DatabaseConfig::logActivity($_SESSION['user_id'], 'LOGIN_SUCCESS', $_SERVER['REMOTE_ADDR']); } catch (Exception $e) {}
        
        $role = $_SESSION['role'];
        if ($role === 'admin') header("Location: ../admin/dashboard.php");
        elseif ($role === 'vet') header("Location: ../vet/dashboard.php");
        else header("Location: ../farmer/dashboard.php");
        exit();
    } else {
        $error = 'Invalid username or password!';
        try { DatabaseConfig::logActivity(0, 'LOGIN_FAILED', $username . ' - ' . $_SERVER['REMOTE_ADDR']); } catch (Exception $e) {}
    }
}

?>


<!DOCTYPE html>
<html>
<head>
    <title>Login - <?php echo DatabaseConfig::$SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/images/logo1.png.png" alt="Logo" class="login-logo">
                <h2>Welcome Back</h2>
                <p>Login if you already have an account.</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary full-width">Sign In</button>
            </form>
            
            <div class="login-links">
                <a href="forgot_password.php">Forgot Password?</a>
                <a href="register.php">Create Account</a>
            </div>
            <div style="text-align:center;margin-top:1rem;">
                <a href="index.php" style="color:#9ca3af;font-size:0.85rem;text-decoration:none;">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>

<style>
.login-page { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.login-container { width: 100%; max-width: 420px; }

.login-card {
    background: white;
    padding: 3rem;
    border-radius: 24px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.2);
    animation: slideUp 0.6s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-logo { 
    width: 80px; 
    height: auto; 
    margin-bottom: 1rem; 
}

.login-header h2 {
    font-size: 2rem;
    color: #1e2937;
    margin-bottom: 0.5rem;
}

.login-header p { color: #6b7280; }

.input-group {
    margin-bottom: 1.5rem;
}

.input-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.input-group input {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.input-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-primary.full-width {
    width: 100%;
    padding: 1.25rem;
    font-size: 1.1rem;
    margin-top: 0.5rem;
}

.login-links {
    text-align: center;
    margin-top: 2rem;
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
}

.login-links a {
    color: #3b82f6;
    text-decoration: none;
}

.login-links a:hover { text-decoration: underline; }
</style>