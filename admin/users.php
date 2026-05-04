<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// ── ADD USER ──
if ($_POST && isset($_POST['add_user'])) {
    // Check duplicates
    $stmt = $db->prepare("SELECT id FROM users WHERE username=? OR email=?");
    $stmt->execute([$_POST['username'], $_POST['email']]);
    if ($stmt->fetch()) {
        $error = "Username or email already exists.";
    } else {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role, phone, is_active) VALUES (?,?,?,?,?,1)");
        if ($stmt->execute([$_POST['username'], $_POST['email'], $hashed, $_POST['role'], $_POST['phone'] ?? ''])) {
            $message = "User '{$_POST['username']}' created successfully!";
        } else { $error = "Error creating user."; }
    }
}

// ── EDIT USER ──
if ($_POST && isset($_POST['edit_user'])) {
    $stmt = $db->prepare("UPDATE users SET username=?, email=?, role=?, phone=?, is_active=? WHERE id=?");
    if ($stmt->execute([$_POST['username'], $_POST['email'], $_POST['role'], $_POST['phone'], $_POST['is_active'], $_POST['user_id']])) {
        // Reset password only if provided
        if (!empty($_POST['new_password'])) {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, $_POST['user_id']]);
            $message = "User updated and password changed.";
        } else {
            $message = "User updated successfully.";
        }
    } else { $error = "Error updating user."; }
}

// ── DELETE USER ──
if ($_POST && isset($_POST['delete_user'])) {
    if ($_POST['user_id'] == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id=?");
        if ($stmt->execute([$_POST['user_id']])) {
            $message = "User deleted successfully.";
        } else { $error = "Error deleting user."; }
    }
}

// ── FETCH ──
$users = $db->query("SELECT * FROM users ORDER BY role, created_at DESC")->fetchAll();
$role_counts = ['admin'=>0,'farmer'=>0,'vet'=>0];
foreach($users as $u) { if(isset($role_counts[$u['role']])) $role_counts[$u['role']]++; }
?>
<?php include '../includes/header.php'; ?>

<!-- EDIT MODAL -->
<div id="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:999;backdrop-filter:blur(3px);" onclick="closeModal()"></div>

<div id="modal-edit" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;border-radius:20px;padding:2rem;width:90%;max-width:500px;z-index:1000;box-shadow:0 30px 60px rgba(0,0,0,.3);max-height:90vh;overflow-y:auto;">
    <h3 style="margin-bottom:1.5rem;">✏️ Edit User</h3>
    <form method="POST">
        <input type="hidden" name="user_id" id="edit-user-id">
        <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Username *</label>
                <input type="text" name="username" id="edit-username" required
                    style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
            </div>
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Email *</label>
                <input type="email" name="email" id="edit-email" required
                    style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
            </div>
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Phone</label>
                <input type="tel" name="phone" id="edit-phone" placeholder="+256..."
                    style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Role *</label>
                    <select name="role" id="edit-role" style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                        <option value="admin">👨‍💼 Admin</option>
                        <option value="farmer">👨‍🌾 Farmer</option>
                        <option value="vet">🏥 Vet</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Status</label>
                    <select name="is_active" id="edit-active" style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                        <option value="1">✅ Active</option>
                        <option value="0">❌ Inactive</option>
                    </select>
                </div>
            </div>
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">
                    New Password <span style="color:#9ca3af;font-weight:400;">(leave blank to keep current)</span>
                </label>
                <input type="password" name="new_password" id="edit-password" placeholder="Enter new password to change"
                    style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
            </div>
        </div>
        <div style="display:flex;gap:.75rem;">
            <button type="submit" name="edit_user" class="btn-primary" style="flex:1;padding:.85rem;">💾 Save Changes</button>
            <button type="button" onclick="closeModal()" style="flex:1;padding:.85rem;border:2px solid #e5e7eb;border-radius:10px;background:white;cursor:pointer;font-size:.95rem;">Cancel</button>
        </div>
    </form>
</div>

<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">👥</span>
        <div>
            <h1>User Management</h1>
            <p style="color:#6b7280;margin:0;">Create, edit and manage all system users</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="background:#f0fdf4;color:#16a34a;padding:1rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;border:1px solid #bbf7d0;">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:#fef2f2;color:#dc2626;padding:1rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;border:1px solid #fecaca;">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue"><h3><?php echo count($users); ?></h3><p>Total Users</p></div>
        <div class="stat-card purple"><h3><?php echo $role_counts['admin']; ?></h3><p>Admins</p></div>
        <div class="stat-card green"><h3><?php echo $role_counts['farmer']; ?></h3><p>Farmers</p></div>
        <div class="stat-card orange"><h3><?php echo $role_counts['vet']; ?></h3><p>Vets</p></div>
    </div>

    <!-- Add New User -->
    <div class="card">
        <h3 style="margin-bottom:1.25rem;">➕ Create New User</h3>
        <form method="POST">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1rem;">
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Username *</label>
                    <input type="text" name="username" placeholder="e.g. john_farmer" required
                        style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Email *</label>
                    <input type="email" name="email" placeholder="e.g. john@mugwe.ug" required
                        style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Password *</label>
                    <input type="password" name="password" placeholder="Min 6 characters" required
                        style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Phone</label>
                    <input type="tel" name="phone" placeholder="+256700000000"
                        style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Role *</label>
                    <select name="role" required style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                        <option value="">Select Role</option>
                        <option value="admin">👨‍💼 Admin</option>
                        <option value="farmer">👨‍🌾 Farmer</option>
                        <option value="vet">🏥 Veterinarian</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_user" class="btn-primary" style="padding:.85rem 2rem;">👤 Create User</button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="card">
        <h3 style="margin-bottom:1.25rem;">All Users (<?php echo count($users); ?>)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($users as $u):
                    $role_colors = ['admin'=>'#6366f1','farmer'=>'#10b981','vet'=>'#f59e0b'];
                    $rc = $role_colors[$u['role']] ?? '#6b7280';
                    $is_me = $u['id'] == $_SESSION['user_id'];
                ?>
                <tr style="<?php echo $is_me ? 'background:#f0f9ff;' : ''; ?>">
                    <td>
                        <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                        <?php if($is_me): ?><span style="font-size:.75rem;color:#3b82f6;margin-left:.4rem;">(you)</span><?php endif; ?>
                    </td>
                    <td style="font-size:.88rem;color:#6b7280;"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td style="font-size:.88rem;"><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                    <td>
                        <span class="role-badge" style="background:<?php echo $rc;?>20;color:<?php echo $rc;?>;padding:.3rem .8rem;border-radius:20px;font-size:.82rem;font-weight:600;">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['is_active']): ?>
                            <span style="color:#10b981;font-size:.85rem;font-weight:500;">● Active</span>
                        <?php else: ?>
                            <span style="color:#ef4444;font-size:.85rem;font-weight:500;">● Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.85rem;"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                    <td style="white-space:nowrap;">
                        <!-- EDIT BUTTON -->
                        <button onclick="openEdit(<?php echo htmlspecialchars(json_encode($u)); ?>)"
                            style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;padding:.4rem .8rem;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:500;margin-right:.3rem;">
                            ✏️ Edit
                        </button>
                        <!-- DELETE BUTTON — can't delete yourself -->
                        <?php if(!$is_me): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user \'<?php echo htmlspecialchars($u['username']); ?>\'? This cannot be undone.')">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" name="delete_user"
                                style="background:#fef2f2;border:1px solid #fecaca;color:#ef4444;padding:.4rem .8rem;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:500;">
                                🗑️ Delete
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
</div>

<script>
function openEdit(u) {
    document.getElementById('edit-user-id').value  = u.id;
    document.getElementById('edit-username').value = u.username;
    document.getElementById('edit-email').value    = u.email;
    document.getElementById('edit-phone').value    = u.phone || '';
    document.getElementById('edit-role').value     = u.role;
    document.getElementById('edit-active').value   = u.is_active;
    document.getElementById('edit-password').value = '';
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('modal-edit').style.display    = 'block';
}

function closeModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('modal-edit').style.display    = 'none';
}

document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });
</script>

<style>
.role-badge { display:inline-block; }
</style>

<?php include '../includes/footer.php'; ?>