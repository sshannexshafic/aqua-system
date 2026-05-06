<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];

$message = '';
$error = '';

// ================= ADD POND =================
if ($_POST && isset($_POST['add_pond'])) {
    try {
        $stmt = $db->prepare("
            INSERT INTO ponds 
            (farmer_id, name, location, size, depth, pond_type, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?)
        ");

        $stmt->execute([
            $farmer_id,
            $_POST['name'],
            $_POST['location'],
            $_POST['size'],
            $_POST['depth'],
            $_POST['pond_type'],
            $_POST['notes']
        ]);

        $message = "Pond '{$_POST['name']}' added successfully!";
    } catch (Exception $e) {
        $error = "Error adding pond: " . $e->getMessage();
    }
}

// ================= UPDATE STATUS =================
if ($_POST && isset($_POST['update_status'])) {
    $stmt = $db->prepare("UPDATE ponds SET status = ? WHERE id = ? AND farmer_id = ?");
    $stmt->execute([
        $_POST['status'],
        $_POST['pond_id'],
        $farmer_id
    ]);
    $message = "Pond status updated.";
}

// ================= DELETE POND =================
if ($_POST && isset($_POST['delete_pond'])) {
    $stmt = $db->prepare("DELETE FROM ponds WHERE id = ? AND farmer_id = ?");
    $stmt->execute([
        $_POST['pond_id'],
        $farmer_id
    ]);
    $message = "Pond deleted successfully.";
}

// ================= FETCH PONDS (ONLY THIS FARMER) =================
$ponds = $db->prepare("
    SELECT p.*,
        (SELECT COUNT(*) FROM fish_stocks fs WHERE fs.pond_id = p.id) AS stock_types,
        (SELECT COALESCE(SUM(fs2.quantity),0) FROM fish_stocks fs2 WHERE fs2.pond_id = p.id) AS total_fish,
        (SELECT COUNT(*) FROM water_quality wq WHERE wq.pond_id = p.id) AS wq_count,
        (SELECT COALESCE(SUM(h.total_revenue),0) FROM harvest_records h WHERE h.pond_id = p.id) AS revenue,
        (SELECT COUNT(*) FROM health_records hr WHERE hr.pond_id = p.id AND hr.status='open') AS open_cases
    FROM ponds p
    WHERE p.farmer_id = ?
    ORDER BY p.created_at DESC
");

$ponds->execute([$farmer_id]);
$ponds = $ponds->fetchAll();

$status_colors = [
    'active' => '#10b981',
    'inactive' => '#9ca3af',
    'under_maintenance' => '#f59e0b'
];
?>

<?php include '../includes/header.php'; ?>

<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">🏞️</span>
        <div>
            <h1>My Ponds</h1>
            <p style="color:#6b7280;margin:0;">Manage your ponds and farming data</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <!-- ADD POND -->
    <div class="card">
        <h3>➕ Add New Pond</h3>

        <form method="POST">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1rem;">

                <input type="text" name="name" placeholder="Pond Name" required>

                <input type="text" name="location" placeholder="Location" required>

                <input type="number" step="0.01" name="size" placeholder="Size (m²)" required>

                <input type="number" step="0.01" name="depth" placeholder="Depth (m)" required>

                <select name="pond_type" required>
                    <option value="earthen">Earthen</option>
                    <option value="lined">Lined</option>
                    <option value="concrete">Concrete</option>
                    <option value="cage">Cage</option>
                </select>

                <input type="text" name="notes" placeholder="Notes (optional)">
            </div>

            <button type="submit" name="add_pond" class="btn-primary">
                🏞️ Add Pond
            </button>
        </form>
    </div>

    <!-- PONDS LIST -->
    <div class="card">
        <h3>My Ponds (<?= count($ponds) ?>)</h3>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th>Fish</th>
                        <th>Revenue</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($ponds): ?>
                    <?php foreach ($ponds as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td><?= htmlspecialchars($p['location']) ?></td>
                            <td><?= $p['size'] ?> m² / <?= $p['depth'] ?>m</td>
                            <td><?= ucfirst($p['pond_type']) ?></td>
                            <td><?= number_format($p['total_fish']) ?></td>
                            <td>UGX <?= number_format($p['revenue']) ?></td>

                            <td>
                                <form method="POST">
                                    <input type="hidden" name="pond_id" value="<?= $p['id'] ?>">

                                    <select name="status" onchange="this.form.submit()">
                                        <option value="active" <?= $p['status']=='active'?'selected':'' ?>>Active</option>
                                        <option value="inactive" <?= $p['status']=='inactive'?'selected':'' ?>>Inactive</option>
                                        <option value="under_maintenance" <?= $p['status']=='under_maintenance'?'selected':'' ?>>Maintenance</option>
                                    </select>

                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>

                            <td>
                                <form method="POST" onsubmit="return confirm('Delete this pond?')">
                                    <input type="hidden" name="pond_id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="delete_pond" class="btn-danger">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center;color:#6b7280;">
                            No ponds yet. Add your first pond above.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>