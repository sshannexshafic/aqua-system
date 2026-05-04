<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// ── HANDLE EDIT USER ──
if ($_POST && isset($_POST['edit_user'])) {
    $stmt = $db->prepare("UPDATE users SET username=?, email=?, role=?, is_active=? WHERE id=?");
    if ($stmt->execute([$_POST['username'], $_POST['email'], $_POST['role'], $_POST['is_active'], $_POST['user_id']])) {
        $message = "User updated successfully.";
    } else { $error = "Failed to update user."; }
}

// ── HANDLE DELETE USER ──
if ($_POST && isset($_POST['delete_user'])) {
    if ($_POST['user_id'] != $_SESSION['user_id']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id=?");
        if ($stmt->execute([$_POST['user_id']])) {
            $message = "User deleted.";
        } else { $error = "Failed to delete user."; }
    } else { $error = "You cannot delete your own account."; }
}

// ── HANDLE EDIT HARVEST ──
if ($_POST && isset($_POST['edit_harvest'])) {
    $stmt = $db->prepare("UPDATE harvest_records SET quantity=?, total_weight=?, total_revenue=?, harvest_date=?, buyer_name=? WHERE id=?");
    if ($stmt->execute([$_POST['quantity'], $_POST['total_weight'], $_POST['total_revenue'], $_POST['harvest_date'], $_POST['buyer_name'], $_POST['harvest_id']])) {
        $message = "Harvest record updated.";
    } else { $error = "Failed to update harvest."; }
}

// ── HANDLE DELETE HARVEST ──
if ($_POST && isset($_POST['delete_harvest'])) {
    $stmt = $db->prepare("DELETE FROM harvest_records WHERE id=?");
    if ($stmt->execute([$_POST['harvest_id']])) {
        $message = "Harvest record deleted.";
    } else { $error = "Failed to delete harvest."; }
}

// ── HANDLE DELETE WATER QUALITY ──
if ($_POST && isset($_POST['delete_wq'])) {
    $stmt = $db->prepare("DELETE FROM water_quality WHERE id=?");
    if ($stmt->execute([$_POST['wq_id']])) {
        $message = "Water quality record deleted.";
    } else { $error = "Failed to delete record."; }
}

// ── FETCH DATA ──
$total_ponds   = $db->query("SELECT COUNT(*) as c FROM ponds")->fetch()['c'];
$active_ponds  = $db->query("SELECT COUNT(*) as c FROM ponds WHERE status='active'")->fetch()['c'];
$total_fish    = $db->query("SELECT COALESCE(SUM(quantity),0) as c FROM fish_stocks")->fetch()['c'];
$total_revenue = $db->query("SELECT COALESCE(SUM(total_revenue),0) as r FROM harvest_records")->fetch()['r'];
$total_users   = $db->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];
$open_health   = $db->query("SELECT COUNT(*) as c FROM health_records WHERE status='open'")->fetch()['c'] ?? 0;

$recent_wq = $db->query("
    SELECT w.*, p.name AS pond_name
    FROM water_quality w JOIN ponds p ON w.pond_id = p.id
    ORDER BY w.recorded_at DESC LIMIT 8
")->fetchAll();

$recent_harvest = $db->query("
    SELECT h.*, p.name AS pond_name
    FROM harvest_records h JOIN ponds p ON h.pond_id = p.id
    ORDER BY h.harvest_date DESC LIMIT 8
")->fetchAll();

$all_users = $db->query("
    SELECT id, username, email, role, is_active, created_at FROM users ORDER BY created_at DESC
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<!-- EDIT MODALS -->
<div id="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:999;backdrop-filter:blur(3px);" onclick="closeAllModals()"></div>

<!-- Edit Harvest Modal -->
<div id="modal-harvest" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;border-radius:20px;padding:2rem;width:90%;max-width:480px;z-index:1000;box-shadow:0 30px 60px rgba(0,0,0,.3);">
    <h3 style="margin-bottom:1.5rem;">✏️ Edit Harvest Record</h3>
    <form method="POST">
        <input type="hidden" name="harvest_id" id="edit-harvest-id">
        <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Pond</label>
                <input type="text" id="edit-harvest-pond" disabled style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;background:#f9fafb;color:#6b7280;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Quantity (fish)</label>
                    <input type="number" name="quantity" id="edit-harvest-qty" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Total Weight (kg)</label>
                    <input type="number" step="0.01" name="total_weight" id="edit-harvest-weight" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Revenue (UGX)</label>
                    <input type="number" step="0.01" name="total_revenue" id="edit-harvest-rev" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Harvest Date</label>
                    <input type="date" name="harvest_date" id="edit-harvest-date" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
            </div>
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Buyer Name</label>
                <input type="text" name="buyer_name" id="edit-harvest-buyer" style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
            </div>
        </div>
        <div style="display:flex;gap:.75rem;">
            <button type="submit" name="edit_harvest" class="btn-primary" style="flex:1;">💾 Save Changes</button>
            <button type="button" onclick="closeAllModals()" style="flex:1;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;background:white;cursor:pointer;">Cancel</button>
        </div>
    </form>
</div>

<!-- Edit User Modal -->
<div id="modal-user" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;border-radius:20px;padding:2rem;width:90%;max-width:460px;z-index:1000;box-shadow:0 30px 60px rgba(0,0,0,.3);">
    <h3 style="margin-bottom:1.5rem;">✏️ Edit User</h3>
    <form method="POST">
        <input type="hidden" name="user_id" id="edit-user-id">
        <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Username</label>
                <input type="text" name="username" id="edit-user-username" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
            </div>
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Email</label>
                <input type="email" name="email" id="edit-user-email" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Role</label>
                    <select name="role" id="edit-user-role" style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                        <option value="admin">Admin</option>
                        <option value="farmer">Farmer</option>
                        <option value="vet">Vet</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Status</label>
                    <select name="is_active" id="edit-user-active" style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:.75rem;">
            <button type="submit" name="edit_user" class="btn-primary" style="flex:1;">💾 Save Changes</button>
            <button type="button" onclick="closeAllModals()" style="flex:1;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;background:white;cursor:pointer;">Cancel</button>
        </div>
    </form>
</div>

<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">🏠</span>
        <div>
            <h1>Admin Dashboard</h1>
            <p style="color:#6b7280;margin:0;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?> · <?php echo date('l, d F Y'); ?></p>
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
        <div class="stat-card blue"><h3><?php echo $total_ponds; ?></h3><p>Total Ponds (<?php echo $active_ponds; ?> Active)</p></div>
        <div class="stat-card green"><h3><?php echo number_format($total_fish); ?></h3><p>Total Fish in Stock</p></div>
        <div class="stat-card orange"><h3>UGX <?php echo number_format($total_revenue); ?></h3><p>Total Revenue</p></div>
        <div class="stat-card purple"><h3><?php echo $total_users; ?></h3><p>System Users</p></div>
    </div>

    <?php if ($open_health > 0): ?>
    <div class="error" style="margin-bottom:1.5rem;">
        ⚠️ There <?php echo $open_health==1?'is':'are'; ?> <strong><?php echo $open_health; ?> open health case<?php echo $open_health!=1?'s':''; ?></strong> requiring attention.
        <a href="../vet/health_records.php" style="color:inherit;font-weight:700;"> View →</a>
    </div>
    <?php endif; ?>

    <div class="reports-grid">
        <!-- Recent Water Quality -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <h3>💧 Recent Water Quality</h3>
                <a href="../reports/water_quality.php" class="btn-primary btn-sm">View All</a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Pond</th><th>pH</th><th>Temp</th><th>DO</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if ($recent_wq): foreach($recent_wq as $r):
                        $alert = $r['ph_level']<6.5||$r['ph_level']>8.5||$r['dissolved_oxygen']<4;
                    ?>
                    <tr style="<?php echo $alert?'background:#fef2f2;':''; ?>">
                        <td><?php echo htmlspecialchars($r['pond_name']); ?></td>
                        <td style="color:<?php echo ($r['ph_level']<6.5||$r['ph_level']>8.5)?'#ef4444':'inherit'; ?>"><?php echo $r['ph_level']; ?></td>
                        <td><?php echo $r['water_temp']; ?>°C</td>
                        <td style="color:<?php echo $r['dissolved_oxygen']<4?'#ef4444':'inherit'; ?>"><?php echo $r['dissolved_oxygen']; ?></td>
                        <td><?php echo date('d M', strtotime($r['recorded_at'])); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this water quality reading?')">
                                <input type="hidden" name="wq_id" value="<?php echo $r['id']; ?>">
                                <button type="submit" name="delete_wq" style="background:#fef2f2;border:1px solid #fecaca;color:#ef4444;padding:.3rem .6rem;border-radius:6px;cursor:pointer;font-size:.8rem;">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center;color:#6b7280;padding:1.5rem;">No readings yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3>⚡ Quick Actions</h3>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
                <a href="ponds.php" class="btn-primary" style="text-align:center;">🏞️ Manage Ponds</a>
                <a href="fish_stocks.php" class="btn-primary" style="text-align:center;background:linear-gradient(135deg,#10b981,#059669);">🐟 Fish Stocks</a>
                <a href="users.php" class="btn-secondary" style="text-align:center;">👥 Manage Users</a>
                <a href="reports.php" class="btn-secondary" style="text-align:center;">📊 Reports</a>
                <a href="../reports/financial_summary.php" class="btn-secondary" style="text-align:center;">💰 Financial Summary</a>
            </div>
        </div>
    </div>

    <!-- Recent Harvests with Edit/Delete -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3>🌾 Recent Harvests</h3>
            <a href="../reports/financial_summary.php" class="btn-primary btn-sm">View All</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Pond</th><th>Fish</th><th>Weight (kg)</th><th>Revenue (UGX)</th><th>Buyer</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($recent_harvest): foreach($recent_harvest as $h): ?>
                <tr>
                    <td><?php echo htmlspecialchars($h['pond_name']); ?></td>
                    <td class="number"><?php echo number_format($h['quantity']); ?></td>
                    <td class="number"><?php echo number_format($h['total_weight'],2); ?></td>
                    <td class="number">UGX <?php echo number_format($h['total_revenue']); ?></td>
                    <td><?php echo htmlspecialchars($h['buyer_name'] ?? '—'); ?></td>
                    <td><?php echo date('d M Y', strtotime($h['harvest_date'])); ?></td>
                    <td style="white-space:nowrap;">
                        <button onclick="openEditHarvest(<?php echo htmlspecialchars(json_encode($h)); ?>)"
                            style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;padding:.3rem .7rem;border-radius:6px;cursor:pointer;font-size:.8rem;margin-right:.3rem;">✏️</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this harvest record?')">
                            <input type="hidden" name="harvest_id" value="<?php echo $h['id']; ?>">
                            <button type="submit" name="delete_harvest" style="background:#fef2f2;border:1px solid #fecaca;color:#ef4444;padding:.3rem .7rem;border-radius:6px;cursor:pointer;font-size:.8rem;">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" style="text-align:center;color:#6b7280;padding:1.5rem;">No harvests recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Users Management with Edit/Delete -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3>👥 System Users</h3>
            <a href="users.php" class="btn-primary btn-sm">Manage All</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($all_users as $u):
                    $role_colors = ['admin'=>'#6366f1','farmer'=>'#10b981','vet'=>'#f59e0b'];
                    $rc = $role_colors[$u['role']] ?? '#6b7280';
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                    <td style="font-size:.85rem;color:#6b7280;"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="role-badge" style="background:<?php echo $rc;?>20;color:<?php echo $rc;?>;">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['is_active']): ?>
                            <span style="color:#10b981;font-size:.85rem;">● Active</span>
                        <?php else: ?>
                            <span style="color:#ef4444;font-size:.85rem;">● Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.85rem;"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                    <td style="white-space:nowrap;">
                        <button onclick="openEditUser(<?php echo htmlspecialchars(json_encode($u)); ?>)"
                            style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;padding:.3rem .7rem;border-radius:6px;cursor:pointer;font-size:.8rem;margin-right:.3rem;">✏️ Edit</button>
                        <?php if($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user \'<?php echo htmlspecialchars($u['username']); ?>\'? This cannot be undone.')">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" name="delete_user" style="background:#fef2f2;border:1px solid #fecaca;color:#ef4444;padding:.3rem .7rem;border-radius:6px;cursor:pointer;font-size:.8rem;">🗑️ Delete</button>
                        </form>
                        <?php else: ?>
                            <span style="font-size:.75rem;color:#9ca3af;">(you)</span>
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
function openEditHarvest(h) {
    document.getElementById('edit-harvest-id').value    = h.id;
    document.getElementById('edit-harvest-pond').value  = h.pond_name;
    document.getElementById('edit-harvest-qty').value   = h.quantity;
    document.getElementById('edit-harvest-weight').value= h.total_weight;
    document.getElementById('edit-harvest-rev').value   = h.total_revenue;
    document.getElementById('edit-harvest-date').value  = h.harvest_date;
    document.getElementById('edit-harvest-buyer').value = h.buyer_name || '';
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('modal-harvest').style.display  = 'block';
}

function openEditUser(u) {
    document.getElementById('edit-user-id').value       = u.id;
    document.getElementById('edit-user-username').value = u.username;
    document.getElementById('edit-user-email').value    = u.email;
    document.getElementById('edit-user-role').value     = u.role;
    document.getElementById('edit-user-active').value   = u.is_active;
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('modal-user').style.display    = 'block';
}

function closeAllModals() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('modal-harvest').style.display  = 'none';
    document.getElementById('modal-user').style.display     = 'none';
}

// Close on Escape key
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeAllModals(); });
</script>

<?php include '../includes/footer.php'; ?>