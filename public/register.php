<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

$error = '';
$success = '';

if ($_POST) {
    $username = sanitize($_POST['username']);
    $email    = sanitize($_POST['email']);
    $phone    = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['farmer', 'vet'])) {
        $error = 'Please select a valid role.';
    } else {
        try {
            $pdo = DatabaseConfig::getConnection();

            // Check username separately
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'That username is already taken. Please choose another.';
            }

            // Check email separately
            if (!$error) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'That email is already registered. Try logging in instead.';
                }
            }

            // Check phone only if provided
            if (!$error && !empty($phone)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->execute([$phone]);
                if ($stmt->fetch()) {
                    $error = 'That phone number is already registered. Use a different one.';
                }
            }

            if (!$error) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                if ($stmt->execute([$username, $email, $hashed, $role, $phone ?: null])) {
                    $success = 'Account created successfully! You can now login.';
                } else {
                    $error = 'Registration failed. Please try again.';
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
            <div class="login-header">
                <img src="../assets/images/logo1.png.png" alt="Logo" class="login-logo">
                <h2>Create Account</h2>
                <p>Join Aquaculture Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="error" style="background:#fef2f2;color:#dc2626;padding:1rem;border-radius:8px;margin-bottom:1rem;border:1px solid #fecaca;">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success" style="background:#f0fdf4;color:#16a34a;padding:1rem;border-radius:8px;margin-bottom:1rem;border:1px solid #bbf7d0;">
                    ✅ <?php echo $success; ?>
                    <br><br>
                    <a href="login.php" class="btn-primary" style="display:inline-block;text-align:center;">Go to Login →</a>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" class="login-form">
                <div class="input-group">
                    <label>Username *</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                </div>
                <div class="input-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="input-group">
                    <label>Phone <span style="color:#9ca3af;font-weight:400;">(optional)</span></label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="+256...">
                </div>
                <div class="input-group">
                    <label>Password * <span style="color:#9ca3af;font-weight:400;">(min 6 characters)</span></label>
                    <input type="password" name="password" required>
                </div>
                <div class="input-group">
                    <label>Role *</label>
                    <select name="role" required style="width:100%;padding:1rem 1.25rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;">
                        <option value="">Select Role</option>
                        <option value="farmer" <?php echo (($_POST['role'] ?? '') === 'farmer') ? 'selected' : ''; ?>>🌾 Farmer / Manager</option>
                        <option value="vet" <?php echo (($_POST['role'] ?? '') === 'vet') ? 'selected' : ''; ?>>🏥 Veterinarian</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary full-width" style="width:100%;padding:1.25rem;font-size:1.1rem;margin-top:0.5rem;">
                    Create Account
                </button>
            </form>
            <?php endif; ?>

            <div class="login-links" style="text-align:center;margin-top:1.5rem;display:flex;justify-content:space-between;font-size:0.9rem;">
                <a href="login.php" style="color:#3b82f6;text-decoration:none;">← Back to Login</a>
                <a href="index.php" style="color:#9ca3af;text-decoration:none;">Home</a>
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
.login-container { width: 100%; max-width: 440px; }
.login-card {
    background: white;
    padding: 3rem;
    border-radius: 24px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.2);
    animation: slideUp 0.6s ease;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}
.login-header { text-align: center; margin-bottom: 2rem; }
.login-logo { width: 80px; height: auto; margin-bottom: 1rem; }
.login-header h2 { font-size: 2rem; color: #1e2937; margin-bottom: 0.25rem; }
.login-header p { color: #6b7280; }
.input-group { margin-bottom: 1.25rem; }
.input-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; }
.input-group input {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-sizing: border-box;
}
.input-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.btn-primary.full-width { width: 100%; }
</style>