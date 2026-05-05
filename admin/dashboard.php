<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// ====================== POST HANDLERS ======================
if ($_POST) {

    // Edit User
    if (isset($_POST['edit_user'])) {
        $stmt = $db->prepare("UPDATE users SET username=?, email=?, role=?, is_active=? WHERE id=?");
        if ($stmt->execute([$_POST['username'], $_POST['email'], $_POST['role'], $_POST['is_active'], $_POST['user_id']])) {
            $message = "✅ User updated successfully.";
        } else { 
            $error = "❌ Failed to update user."; 
        }
    }

    // Delete User
    if (isset($_POST['delete_user'])) {
        if ($_POST['user_id'] != $_SESSION['user_id']) {
            $stmt = $db->prepare("DELETE FROM users WHERE id=?");
            if ($stmt->execute([$_POST['user_id']])) {
                $message = "✅ User deleted successfully.";
            } else { 
                $error = "❌ Failed to delete user."; 
            }
        } else { 
            $error = "❌ You cannot delete your own account."; 
        }
    }

    // Edit Harvest (Updated column names)
    if (isset($_POST['edit_harvest'])) {
        try {
            $stmt = $db->prepare("
                UPDATE harvest_records 
                SET quantity_kg = ?, total_value = ?, harvest_date = ?, buyer_name = ?, notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                floatval($_POST['quantity_kg']),
                floatval($_POST['total_value']),
                $_POST['harvest_date'],
                trim($_POST['buyer_name'] ?? ''),
                trim($_POST['notes'] ?? ''),
                $_POST['harvest_id']
            ]);
            $message = "✅ Harvest record updated successfully.";
        } catch (Exception $e) {
            $error = "❌ Failed to update harvest.";
        }
    }

    // Delete Harvest
    if (isset($_POST['delete_harvest'])) {
        $stmt = $db->prepare("DELETE FROM harvest_records WHERE id=?");
        if ($stmt->execute([$_POST['harvest_id']])) {
            $message = "✅ Harvest record deleted.";
        } else { 
            $error = "❌ Failed to delete harvest."; 
        }
    }

    // Delete Water Quality
    if (isset($_POST['delete_wq'])) {
        $stmt = $db->prepare("DELETE FROM water_quality WHERE id=?");
        if ($stmt->execute([$_POST['wq_id']])) {
            $message = "✅ Water quality record deleted.";
        } else { 
            $error = "❌ Failed to delete record."; 
        }
    }
}

// ====================== FETCH DATA (Safe) ======================
$total_ponds   = $db->query("SELECT COUNT(*) as c FROM ponds")->fetch()['c'] ?? 0;
$active_ponds  = $db->query("SELECT COUNT(*) as c FROM ponds WHERE status='active'")->fetch()['c'] ?? 0;

$total_fish = 0;
try {
    $total_fish = $db->query("SELECT COALESCE(SUM(current_stock), 0) FROM ponds")->fetchColumn() ?? 0;
} catch(Exception $e) {}

$total_revenue = 0;
try {
    $total_revenue = $db->query("SELECT COALESCE(SUM(total_value), 0) FROM harvest_records")->fetchColumn() ?? 0;
} catch(Exception $e) {}

$total_expenses = 0;
try {
    $total_expenses = $db->query("SELECT COALESCE(SUM(amount), 0) FROM expenses")->fetchColumn() ?? 0;
} catch(Exception $e) {}

$net_profit = $total_revenue - $total_expenses;

$total_users = $db->query("SELECT COUNT(*) as c FROM users")->fetch()['c'] ?? 0;
$open_health = $db->query("SELECT COUNT(*) as c FROM health_records WHERE status='open'")->fetch()['c'] ?? 0;

// Last 30 Days Summary
$month_summary = $db->query("
    SELECT COUNT(*) as harvests, 
           COALESCE(SUM(quantity_kg),0) as kg, 
           COALESCE(SUM(total_value),0) as revenue 
    FROM harvest_records 
    WHERE harvest_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetch();

// Recent Data
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
    SELECT id, username, email, role, is_active, created_at 
    FROM users ORDER BY created_at DESC LIMIT 12
")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

<!-- MODALS -->
<div id="modal-overlay" onclick="closeAllModals()"></div>

<!-- Edit Harvest Modal (Updated) -->
<div id="modal-harvest" class="modal">
    <h3>✏️ Edit Harvest Record</h3>
    <form method="POST">
        <input type="hidden" name="harvest_id" id="edit-harvest-id">
        <div class="modal-content">
            <label>Pond</label>
            <input type="text" id="edit-harvest-pond" disabled>

            <div class="modal-grid">
                <div>
                    <label>Quantity (kg)</label>
                    <input type="number" step="0.01" name="quantity_kg" id="edit-harvest-qty" required>
                </div>
                <div>
                    <label>Total Value (UGX)</label>
                    <input type="number" step="0.01" name="total_value" id="edit-harvest-value" required>
                </div>
            </div>

            <label>Harvest Date</label>
            <input type="date" name="harvest_date" id="edit-harvest-date" required>

            <label>Buyer Name</label>
            <input type="text" name="buyer_name" id="edit-harvest-buyer">

            <label>Notes</label>
            <textarea name="notes" id="edit-harvest-notes" rows="3"></textarea>
        </div>

        <div class="modal-buttons">
            <button type="submit" name="edit_harvest" class="btn-primary">💾 Save Changes</button>
            <button type="button" onclick="closeAllModals()" class="btn-secondary">Cancel</button>
        </div>
    </form>
</div>

<!-- Edit User Modal -->
<div id="modal-user" class="modal">
    <h3>✏️ Edit User</h3>
    <form method="POST">
        <input type="hidden" name="user_id" id="edit-user-id">
        <div class="modal-content">
            <label>Username</label>
            <input type="text" name="username" id="edit-username" required>
            
            <label>Email</label>
            <input type="email" name="email" id="edit-email" required>
            
            <label>Role</label>
            <select name="role" id="edit-role">
                <option value="admin">Admin</option>
                <option value="vet">Veterinarian</option>
                <option value="staff">Staff</option>
            </select>
            
            <label>Status</label>
            <select name="is_active" id="edit-active">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <div class="modal-buttons">
            <button type="submit" name="edit_user" class="btn-primary">💾 Save Changes</button>
            <button type="button" onclick="closeAllModals()" class="btn-secondary">Cancel</button>
        </div>
    </form>
</div>

<div class="admin-page">

    <div class="page-header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-size:2.5rem;">🏠</span>
                <div>
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> · <?= date('l, d F Y') ?></p>
                </div>
            </div>
            <a href="reports/financial_summary.php" class="btn-financial-report">
                📊 View Full Financial Report →
            </a>
        </div>
    </div>

    <?php if ($message): ?><div class="success"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

    <!-- Stats Grid with Financial Focus -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <h3><?= $total_ponds ?></h3>
            <p>Total Ponds (<?= $active_ponds ?> Active)</p>
        </div>
        <div class="stat-card green">
            <h3><?= number_format($total_fish) ?> kg</h3>
            <p>Total Fish Stock</p>
        </div>
        <div class="stat-card orange">
            <h3>UGX <?= number_format($total_revenue) ?></h3>
            <p>Total Revenue</p>
            <small>From harvests</small>
        </div>
        <div class="stat-card red">
            <h3>UGX <?= number_format($total_expenses) ?></h3>
            <p>Total Expenses</p>
            <small>Operational costs</small>
        </div>
        <div class="stat-card purple">
            <h3 class="<?= $net_profit >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                UGX <?= number_format(abs($net_profit)) ?>
                <?= $net_profit >= 0 ? '▲' : '▼' ?>
            </h3>
            <p>Net Profit</p>
            <small>Revenue - Expenses</small>
        </div>
        <div class="stat-card cyan">
            <h3><?= $total_users ?></h3>
            <p>System Users</p>
        </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="quick-actions">
        <a href="reports/financial_summary.php" class="action-card">
            <span class="action-icon">💰</span>
            <div>
                <h4>Financial Report</h4>
                <p>View detailed financial analysis</p>
            </div>
        </a>
        <a href="../harvest/add_harvest.php" class="action-card">
            <span class="action-icon">🌾</span>
            <div>
                <h4>Record Harvest</h4>
                <p>Add new harvest record</p>
            </div>
        </a>
        <a href="../expenses/add_expense.php" class="action-card">
            <span class="action-icon">📝</span>
            <div>
                <h4>Add Expense</h4>
                <p>Track operational costs</p>
            </div>
        </a>
        <a href="../ponds/manage_ponds.php" class="action-card">
            <span class="action-icon">🏞️</span>
            <div>
                <h4>Manage Ponds</h4>
                <p>View and edit ponds</p>
            </div>
        </a>
    </div>

    <!-- 30 Days Performance Summary -->
    <div class="card">
        <div class="card-header">
            <h3>📊 Performance - Last 30 Days</h3>
            <a href="reports/financial_summary.php?range=month" class="btn-link">View Details →</a>
        </div>
        <div class="financial-grid">
            <div class="metric">
                <strong><?= number_format($month_summary['kg'], 1) ?> kg</strong>
                <span>Harvested</span>
            </div>
            <div class="metric">
                <strong>UGX <?= number_format($month_summary['revenue']) ?></strong>
                <span>Revenue</span>
            </div>
            <div class="metric">
                <strong><?= $month_summary['harvests'] ?></strong>
                <span>Harvest Events</span>
            </div>
        </div>
        <div class="progress-bar-container">
            <div class="progress-label">
                <span>Monthly Target Progress</span>
                <span><?= min(100, round(($month_summary['revenue'] / 10000000) * 100)) ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= min(100, round(($month_summary['revenue'] / 10000000) * 100)) ?>%"></div>
            </div>
            <small>Target: UGX 10,000,000 per month</small>
        </div>
    </div>

    <?php if ($open_health > 0): ?>
    <div class="alert alert-warning">
        ⚠️ There <?= $open_health==1?'is':'are' ?> <strong><?= $open_health ?></strong> open health case<?= $open_health!=1?'s':'' ?>.
        <a href="../vet/health_records.php">View Now →</a>
    </div>
    <?php endif; ?>

    <!-- Two Column Layout for Recent Data -->
    <div class="two-column-layout">
        <!-- Recent Harvests -->
        <div class="card">
            <div class="card-header">
                <h3>🌾 Recent Harvests</h3>
                <a href="reports/financial_summary.php" class="btn-link">View All →</a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pond</th>
                            <th>Qty (kg)</th>
                            <th>Value (UGX)</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($recent_harvest as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['pond_name']) ?></td>
                        <td class="number"><?= number_format($h['quantity_kg'] ?? 0, 2) ?></td>
                        <td class="number"><strong>UGX <?= number_format($h['total_value'] ?? 0) ?></strong></td>
                        <td><?= date('d M Y', strtotime($h['harvest_date'])) ?></td>
                        <td>
                            <button onclick="openEditHarvest(<?= htmlspecialchars(json_encode($h)) ?>)" class="btn-small" title="Edit">✏️</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this harvest?')">
                                <input type="hidden" name="harvest_id" value="<?= $h['id'] ?>">
                                <button type="submit" name="delete_harvest" class="btn-small danger" title="Delete">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Water Quality -->
        <div class="card">
            <div class="card-header">
                <h3>💧 Recent Water Quality</h3>
                <a href="../water_quality/view.php" class="btn-link">View All →</a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pond</th>
                            <th>pH</th>
                            <th>Temperature</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($recent_wq as $w): ?>
                    <tr>
                        <td><?= htmlspecialchars($w['pond_name']) ?></td>
                        <td><?= htmlspecialchars($w['ph'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($w['temperature'] ?? '—') ?>°C</td>
                        <td><?= date('d M Y', strtotime($w['recorded_at'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                                <input type="hidden" name="wq_id" value="<?= $w['id'] ?>">
                                <button type="submit" name="delete_wq" class="btn-small danger" title="Delete">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Users Section -->
    <div class="card">
        <div class="card-header">
            <h3>👥 Recent Users</h3>
            <a href="users.php" class="btn-link">Manage Users →</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($all_users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= ucfirst($u['role']) ?></td>
                    <td>
                        <span class="badge <?= $u['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <button onclick="openEditUser(<?= htmlspecialchars(json_encode($u)) ?>)" class="btn-small" title="Edit">✏️</button>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?')">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" name="delete_user" class="btn-small danger" title="Delete">🗑️</button>
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
// Updated Edit Harvest Function
function openEditHarvest(h) {
    document.getElementById('edit-harvest-id').value = h.id;
    document.getElementById('edit-harvest-pond').value = h.pond_name || '';
    document.getElementById('edit-harvest-qty').value = h.quantity_kg || '';
    document.getElementById('edit-harvest-value').value = h.total_value || '';
    document.getElementById('edit-harvest-date').value = h.harvest_date;
    document.getElementById('edit-harvest-buyer').value = h.buyer_name || '';
    document.getElementById('edit-harvest-notes').value = h.notes || '';
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('modal-harvest').style.display = 'block';
}

// Edit User Function
function openEditUser(u) {
    document.getElementById('edit-user-id').value = u.id;
    document.getElementById('edit-username').value = u.username;
    document.getElementById('edit-email').value = u.email;
    document.getElementById('edit-role').value = u.role;
    document.getElementById('edit-active').value = u.is_active;
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('modal-user').style.display = 'block';
}

function closeAllModals() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('modal-harvest').style.display = 'none';
    document.getElementById('modal-user').style.display = 'none';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeAllModals();
});
</script>

<style>
/* Additional Styles for Admin Page */
.profit-positive {
    color: #27ae60;
}

.profit-negative {
    color: #e74c3c;
}

.btn-financial-report {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: transform 0.3s ease;
    display: inline-block;
}

.btn-financial-report:hover {
    transform: translateY(-2px);
    color: white;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.action-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.action-icon {
    font-size: 2rem;
}

.action-card h4 {
    margin: 0 0 5px 0;
    font-size: 1rem;
}

.action-card p {
    margin: 0;
    font-size: 0.85rem;
    color: #7f8c8d;
}

.two-column-layout {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.card-header h3 {
    margin: 0;
}

.btn-link {
    color: #3498db;
    text-decoration: none;
    font-size: 0.85rem;
}

.btn-link:hover {
    text-decoration: underline;
}

.stat-card.red {
    border-left: 4px solid #e74c3c;
}

.stat-card.cyan {
    border-left: 4px solid #00bcd4;
}

.metric {
    text-align: center;
    padding: 10px;
}

.metric strong {
    display: block;
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.metric span {
    color: #7f8c8d;
    font-size: 0.85rem;
}

.progress-bar-container {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.85rem;
}

.progress-bar {
    background: #ecf0f1;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    background: linear-gradient(90deg, #3498db, #2ecc71);
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
}

.badge-success {
    background: #d5f4e6;
    color: #27ae60;
}

.badge-danger {
    background: #fce4e4;
    color: #e74c3c;
}

@media (max-width: 768px) {
    .two-column-layout {
        grid-template-columns: 1fr;
    }
    
    .page-header > div:first-child {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .btn-financial-report {
        margin-top: 10px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}
</style>

<?php include '../includes/footer.php'; ?>