cat > /home/claude/aquaculturesystem_final/vet/mortality.php << 'PHPEOF'
<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('vet');

$database = new Database();
$db = $database->getConnection();

$message = ''; $error = '';

if ($_POST && isset($_POST['add_mortality'])) {
    try {
        $loss = round(floatval($_POST['count']) * floatval($_POST['estimated_weight']) * floatval($_POST['price_per_kg']));
        $stmt = $db->prepare("
            INSERT INTO mortality_records (pond_id, reported_by, mortality_date, count, estimated_weight, cause, probable_reason, action_taken, loss_value, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['pond_id'], $_SESSION['user_id'], $_POST['mortality_date'],
            $_POST['count'], $_POST['estimated_weight'], $_POST['cause'],
            $_POST['probable_reason'], $_POST['action_taken'], $loss, $_POST['notes']
        ]);
        $message = 'Mortality record saved.';
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

if ($_POST && isset($_POST['delete_mortality'])) {
    $db->prepare("DELETE FROM mortality_records WHERE id=?")->execute([$_POST['record_id']]);
    $message = 'Record deleted.';
}

$ponds   = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();
$records = $db->query("
    SELECT m.*, p.name AS pond_name, u.username AS reporter
    FROM mortality_records m
    JOIN ponds p ON m.pond_id = p.id
    LEFT JOIN users u ON m.reported_by = u.id
    ORDER BY m.mortality_date DESC
")->fetchAll();

$total_deaths = array_sum(array_column($records, 'count'));
$total_loss   = array_sum(array_column($records, 'loss_value'));
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">💀</span>
        <h1>Mortality Records</h1>
    </div>

    <?php if ($message): ?><div class="success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Summary -->
    <div class="stats-grid">
        <div class="stat-card" style="border-top-color:#ef4444;">
            <h3 style="color:#ef4444;"><?php echo number_format($total_deaths); ?></h3>
            <p>Total Deaths Recorded</p>
        </div>
        <div class="stat-card orange">
            <h3>UGX <?php echo number_format($total_loss); ?></h3>
            <p>Estimated Total Loss</p>
        </div>
    </div>

    <!-- Add Record -->
    <div class="card">
        <h3>➕ Log Mortality Event</h3>
        <form method="POST">
            <div class="form-grid">
                <select name="pond_id" required>
                    <option value="">-- Select Pond --</option>
                    <?php foreach($ponds as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="mortality_date" value="<?php echo date('Y-m-d'); ?>" required>
                <input type="number" name="count" placeholder="Number of fish died *" min="1" required>
                <input type="number" name="estimated_weight" step="0.01" placeholder="Avg weight per fish (kg)">
                <input type="number" name="price_per_kg" step="1" placeholder="Market price/kg (UGX)">
                <select name="probable_reason" required>
                    <option value="unknown">Unknown</option>
                    <option value="disease">Disease</option>
                    <option value="poor_water_quality">Poor Water Quality</option>
                    <option value="oxygen_depletion">Oxygen Depletion</option>
                    <option value="predation">Predation</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
                <input type="text" name="cause" placeholder="Specific cause or disease name"
                    style="padding:1rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;">
                <textarea name="action_taken" rows="2" placeholder="Immediate action taken"
                    style="padding:1rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;resize:vertical;"></textarea>
                <textarea name="notes" rows="2" placeholder="Additional notes"
                    style="padding:1rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;resize:vertical;"></textarea>
            </div>
            <button type="submit" name="add_mortality" class="btn-danger">💾 Save Mortality Record</button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="card">
        <h3>📋 All Mortality Records</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Pond</th><th>Date</th><th>Count</th><th>Est. Weight (kg)</th><th>Probable Cause</th><th>Est. Loss (UGX)</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php if ($records): foreach($records as $r): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($r['pond_name']); ?></strong></td>
                    <td><?php echo date('d M Y', strtotime($r['mortality_date'])); ?></td>
                    <td class="number" style="color:#ef4444;"><?php echo number_format($r['count']); ?></td>
                    <td><?php echo number_format($r['estimated_weight'],2); ?></td>
                    <td><?php echo ucwords(str_replace('_',' ',$r['probable_reason'])); ?></td>
                    <td class="number">UGX <?php echo number_format($r['loss_value']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="record_id" value="<?php echo $r['id']; ?>">
                            <button type="submit" name="delete_mortality" class="btn-danger btn-sm"
                                data-confirm="Delete this mortality record?">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" style="text-align:center;color:#6b7280;padding:2rem;">No mortality records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
PHPEOF