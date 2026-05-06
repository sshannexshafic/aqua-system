<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

if ($_POST) {
    if (isset($_POST['add_pond'])) {
        $stmt = $db->prepare("INSERT INTO ponds (name, location, size, depth, pond_type, status, notes) VALUES (?, ?, ?, ?, ?, 'active', ?)");
        if ($stmt->execute([
            $_POST['name'], $_POST['location'], $_POST['size'],
            $_POST['depth'], $_POST['pond_type'], $_POST['notes']
        ])) {
            $message = "Pond '{$_POST['name']}' added successfully! All farmers can now access it.";
        } else {
            $error = "Error adding pond. Please try again.";
        }
    }

    if (isset($_POST['update_status'])) {
        $stmt = $db->prepare("UPDATE ponds SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['pond_id']]);
        $message = "Pond status updated.";
    }

    if (isset($_POST['delete_pond'])) {
        $stmt = $db->prepare("DELETE FROM ponds WHERE id = ?");
        if ($stmt->execute([$_POST['pond_id']])) {
            $message = "Pond deleted successfully.";
        }
    }
}

$ponds = $db->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM fish_stocks fs WHERE fs.pond_id = p.id) AS stock_types,
           (SELECT COALESCE(SUM(fs2.quantity),0) FROM fish_stocks fs2 WHERE fs2.pond_id = p.id) AS total_fish,
           (SELECT COUNT(*) FROM water_quality wq WHERE wq.pond_id = p.id) AS wq_count,
           (SELECT COALESCE(SUM(h.total_revenue),0) FROM harvest_records h WHERE h.pond_id = p.id) AS revenue,
           (SELECT COUNT(*) FROM health_records hr WHERE hr.pond_id = p.id AND hr.status='open') AS open_cases
    FROM ponds p
    ORDER BY p.created_at DESC
")->fetchAll();

$status_colors = ['active'=>'#10b981','inactive'=>'#9ca3af','under_maintenance'=>'#f59e0b'];
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">🏞️</span>
        <div>
            <h1>Ponds Management</h1>
            <p style="color:#6b7280;margin:0;">All ponds are shared — every farmer can view and record data on any pond</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="success" style="background:#f0fdf4;color:#16a34a;padding:1rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;border:1px solid #bbf7d0;">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error" style="background:#fef2f2;color:#dc2626;padding:1rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;border:1px solid #fecaca;">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue"><h3><?php echo count($ponds); ?></h3><p>Total Ponds</p></div>
        <div class="stat-card green"><h3><?php echo count(array_filter($ponds, fn($p)=>$p['status']==='active')); ?></h3><p>Active Ponds</p></div>
        <div class="stat-card orange"><h3>UGX <?php echo number_format(array_sum(array_column($ponds,'revenue'))); ?></h3><p>Total Revenue</p></div>
        <div class="stat-card purple"><h3><?php echo number_format(array_sum(array_column($ponds,'total_fish'))); ?></h3><p>Total Fish</p></div>
    </div>

    <!-- Add New Pond -->
    <div class="card">
        <h3>➕ Register New Pond</h3>
        <p style="color:#6b7280;font-size:.9rem;margin-bottom:1.5rem;">Once added, all farmers will immediately be able to see and record data for this pond.</p>
        <form method="POST">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1rem;">
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Pond Name *</label>
                    <input type="text" name="name" placeholder="e.g. Pond A" required
                           style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Location *</label>
                    <input type="text" name="location" placeholder="e.g. Busolwe North" required
                           style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Size (m²) *</label>
                    <input type="number" step="0.01" name="size" placeholder="e.g. 500" required
                           style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Depth (m) *</label>
                    <input type="number" step="0.01" name="depth" placeholder="e.g. 1.5" required
                           style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Pond Type</label>
                    <select name="pond_type" style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                        <option value="earthen">Earthen</option>
                        <option value="lined">Lined</option>
                        <option value="concrete">Concrete</option>
                        <option value="cage">Cage</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Notes</label>
                    <input type="text" name="notes" placeholder="Optional notes"
                           style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
            </div>
            <button type="submit" name="add_pond" class="btn-primary" style="padding:.85rem 2rem;">🏞️ Add Pond</button>
        </form>
    </div>

    <!-- Ponds Table -->
    <div class="card">
        <h3>All Ponds (<?php echo count($ponds); ?>)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pond Name</th>
                        <th>Location</th>
                        <th>Size / Depth</th>
                        <th>Type</th>
                        <th>Fish</th>
                        <th>WQ Records</th>
                        <th>Revenue</th>
                        <th>Health</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($ponds): foreach($ponds as $p):
                    $sc = $status_colors[$p['status']] ?? '#6b7280';
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                        <small style="color:#6b7280;"><?php echo htmlspecialchars($p['notes'] ?? ''); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($p['location']); ?></td>
                    <td><?php echo number_format($p['size'],0); ?> m² / <?php echo $p['depth']; ?>m</td>
                    <td><?php echo ucfirst($p['pond_type'] ?? 'earthen'); ?></td>
                    <td class="number"><?php echo number_format($p['total_fish']); ?></td>
                    <td class="number"><?php echo $p['wq_count']; ?></td>
                    <td class="number">UGX <?php echo number_format($p['revenue']); ?></td>
                    <td>
                        <?php if($p['open_cases']>0): ?>
                            <span class="role-badge" style="background:#fef2f2;color:#ef4444;"><?php echo $p['open_cases']; ?> Open</span>
                        <?php else: ?>
                            <span style="color:#10b981;">✅ Clear</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="pond_id" value="<?php echo $p['id']; ?>">
                            <select name="status" onchange="this.form.submit()"
                                style="padding:.3rem .6rem;border:1px solid <?php echo $sc;?>;border-radius:6px;color:<?php echo $sc;?>;font-size:.8rem;background:<?php echo $sc;?>15;cursor:pointer;">
                                <option value="active" <?php echo $p['status']==='active'?'selected':''; ?>>Active</option>
                                <option value="inactive" <?php echo $p['status']==='inactive'?'selected':''; ?>>Inactive</option>
                                <option value="under_maintenance" <?php echo $p['status']==='under_maintenance'?'selected':''; ?>>Maintenance</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete pond \'<?php echo htmlspecialchars($p['name']); ?>\'? All data including fish stocks, water records and harvests will be permanently lost!')">
                            <input type="hidden" name="pond_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" name="delete_pond" class="btn-primary btn-sm" style="background:#ef4444;padding:.4rem .8rem;">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="10" style="text-align:center;color:#6b7280;padding:3rem;">No ponds yet. Add your first pond above.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;">
        <div style="display:flex;align-items:center;gap:1rem;">
            <span style="font-size:2rem;">ℹ️</span>
            <div>
                <strong style="color:#166534;">How pond access works</strong>
                <p style="color:#15803d;font-size:.9rem;margin-top:.25rem;">All ponds registered here are visible to <strong>every farmer</strong> in the system. Farmers can record water quality, feed, and harvest data on any pond. The admin can change a pond's status or delete it at any time.</p>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>