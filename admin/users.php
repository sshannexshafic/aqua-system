<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_user'])) {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $data = [
            $_POST['username'],
            $_POST['email'],
            $hashed_password,
            $_POST['role'],
            $_POST['phone']
        ];
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute($data)) {
            $message = "User created successfully!";
        } else {
            $error = "Error creating user!";
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        if ($stmt->execute([$_POST['user_id']])) {
            $message = "User deleted successfully!";
        }
    }
}

// Fetch all users
$users = $db->query("SELECT * FROM users ORDER BY role, created_at DESC")->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="admin-page">
    <div class="page-header">
        <h1><i class="icon-users"></i> User Management</h1>
        <?php if ($message) echo "<div class='success'>$message</div>"; ?>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    </div>

    <!-- Add New User Form -->
    <div class="card">
        <h3>Create New User</h3>
        <form method="POST" class="form-grid">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="tel" name="phone" placeholder="Phone (+256...)" required>
            
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="farmer">Farmer</option>
                <option value="vet">Veterinarian</option>
            </select>
            
            <button type="submit" name="add_user" class="btn-primary">Create User</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="card">
        <h3>All Users (<?php echo count($users); ?>)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr class="<?php echo $user['role']; ?>">
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <span class="role-badge <?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td><span class="status active">Active</span></td>
                        <td>
                            <?php if ($user['role'] != 'admin'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?')">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn-danger btn-sm">Delete</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted">Admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.role-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
.role-badge.farmer { background: #10b981; color: white; }
.role-badge.vet { background: #f59e0b; color: white; }
.status { padding: 4px 8px; border-radius: 12px; font-size: 12px; }
.status.active { background: #dcfce7; color: #166534; }
</style>
<?php include '../includes/footer.php'; ?>