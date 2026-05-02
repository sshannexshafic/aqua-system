<?php
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_POST) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            $pdo = DatabaseConfig::getConnection();
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? OR phone = ?");
            $stmt->execute([$email, $username, $phone]);
            
            if ($stmt->fetch()) {
                $error = 'User already exists with this email, username, or phone';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, role, phone) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$username, $email, $hashed_password, $role, $phone])) {
                    $success = 'Account created successfully! You can now login.';
                } else {
                    $error = 'Registration failed. Try again.';
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - <?php echo DatabaseConfig::$SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <img src="../assets/images/logo.png" alt="Logo" class="login-logo">
            <h2>Create Account</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
            <form method="POST" class="login-form">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Phone (+256...)</label>
                    <input type="tel" name="phone" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="input-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="farmer">Farmer/Manager</option>
                        <option value="vet">Veterinarian</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary full-width">Create Account</button>
            </form>
            <?php endif; ?>
            
            <div class="login-links">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>