<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

$allowed_roles = ['admin', 'farmer', 'vet'];

/* CSRF CHECK */
if ($_POST) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid request (CSRF blocked)");
    }
}

/* ADD USER */
if ($_POST && isset($_POST['add_user'])) {

    if (!in_array($_POST['role'], $allowed_roles)) {
        $error = "Invalid role selected.";
    } elseif (strlen($_POST['password']) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        $stmt = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->execute([$_POST['username'], $_POST['email']]);

        if ($stmt->fetch()) {
            $error = "Username or email already exists.";
        } else {

            $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, role, phone, is_active)
                VALUES (?,?,?,?,?,1)
            ");

            $stmt->execute([
                trim($_POST['username']),
                trim($_POST['email']),
                $hashed,
                $_POST['role'],
                $_POST['phone'] ?? null
            ]);

            $message = "User created successfully!";
        }
    }
}

/* DELETE USER (SOFT) */
if ($_POST && isset($_POST['delete_user'])) {

    if ($_POST['user_id'] == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id=?");
        $stmt->execute([$_POST['user_id']]);
        $message = "User deactivated successfully.";
    }
}

/* USERS */
$users = $db->query("
    SELECT id, username, email, phone, role, is_active
    FROM users
    ORDER BY role, id DESC
")->fetchAll();

$role_counts = ['admin'=>0,'farmer'=>0,'vet'=>0];

foreach ($users as $u) {
    if (isset($role_counts[$u['role']])) {
        $role_counts[$u['role']]++;
    }
}
?>

<?php include '../includes/header.php'; ?>

<!-- FIX ICONS (REQUIRED) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="admin-page">

    <div class="page-header">
        <h1><i class="fa fa-users"></i> User Management</h1>
        <p>Create, edit, and manage system users</p>
    </div>

    <!-- MESSAGES -->
    <?php if ($message): ?>
        <div class="alert success">
            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error">
            <i class="fa fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-grid">

        <div class="stat-card">
            <i class="fa fa-users"></i>
            <h3><?= count($users) ?></h3>
            <p>Total Users</p>
        </div>

        <div class="stat-card">
            <i class="fa fa-user-shield"></i>
            <h3><?= $role_counts['admin'] ?></h3>
            <p>Admins</p>
        </div>

        <div class="stat-card">
            <i class="fa fa-seedling"></i>
            <h3><?= $role_counts['farmer'] ?></h3>
            <p>Farmers</p>
        </div>

        <div class="stat-card">
            <i class="fa fa-user-doctor"></i>
            <h3><?= $role_counts['vet'] ?></h3>
            <p>Vets</p>
        </div>

    </div>

    <!-- CREATE USER -->
    <div class="card">
        <h3><i class="fa fa-user-plus"></i> Create User</h3>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="phone" placeholder="Phone">

            <select name="role" required>
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="farmer">Farmer</option>
                <option value="vet">Vet</option>
            </select>

            <button type="submit" name="add_user" class="btn-primary">
                <i class="fa fa-plus"></i> Create User
            </button>
        </form>
    </div>

    <!-- USERS LIST -->
    <div class="card">
        <h3><i class="fa fa-list"></i> Users List</h3>

        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone']) ?></td>
                    <td><?= ucfirst($u['role']) ?></td>
                    <td>
                        <span class="<?= $u['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>

                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">

                            <button type="submit" name="delete_user" class="btn-danger">
                                <i class="fa fa-trash"></i> Deactivate
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>