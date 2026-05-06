<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];

$message = '';
$error = '';

// ================= SAVE WATER QUALITY =================
if ($_POST && isset($_POST['add_record'])) {
    try {

        $stmt = $db->prepare("
            INSERT INTO water_quality
            (pond_id, recorded_by, water_temp, ph_level, dissolved_oxygen, ammonia, turbidity, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['pond_id'],
            $farmer_id,
            $_POST['water_temp'],
            $_POST['ph_level'],
            $_POST['dissolved_oxygen'],
            $_POST['ammonia'],
            $_POST['turbidity'],
            $_POST['notes']
        ]);

        // ================= ALERT SYSTEM =================
        $alerts = [];

        if ($_POST['ph_level'] < 6.5) {
            $alerts[] = "⚠ Low pH (acidic water)";
        } elseif ($_POST['ph_level'] > 8.5) {
            $alerts[] = "⚠ High pH (alkaline water)";
        }

        if ($_POST['dissolved_oxygen'] < 3) {
            $alerts[] = "🚨 Low oxygen level (dangerous)";
        }

        $message = !empty($alerts)
            ? "Record saved! | " . implode(" | ", $alerts)
            : "Water quality recorded successfully!";

    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// ================= FARMER PONDS =================
$ponds = $db->prepare("
    SELECT id, name 
    FROM ponds 
    WHERE farmer_id = ?
    ORDER BY name
");
$ponds->execute([$farmer_id]);
$ponds = $ponds->fetchAll();

// ================= RECORDS =================
$records = $db->prepare("
    SELECT w.*, p.name AS pond_name
    FROM water_quality w
    JOIN ponds p ON w.pond_id = p.id
    WHERE w.recorded_by = ?
    ORDER BY w.recorded_at DESC
");
$records->execute([$farmer_id]);
$records = $records->fetchAll();

include '../includes/header.php';
?>

<div class="admin-page">

    <div class="page-header">
        <h1>💧 Water Quality Monitoring</h1>
        <p>Track pond water conditions for healthy fish growth</p>
    </div>

    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ================= FORM ================= -->
    <div class="card">
        <h3>➕ Record Water Quality</h3>

        <form method="POST">

            <div class="form-grid">

                <select name="pond_id" required>
                    <option value="">Select Pond</option>
                    <?php foreach($ponds as $p): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="number" step="0.1" name="water_temp" placeholder="Water Temperature (°C)" required>

                <input type="number" step="0.1" name="ph_level" placeholder="pH Level" required>

                <input type="number" step="0.1" name="dissolved_oxygen" placeholder="Dissolved Oxygen (mg/L)" required>

                <input type="number" step="0.001" name="ammonia" placeholder="Ammonia (mg/L)" required>

                <input type="text" name="turbidity" placeholder="Turbidity (e.g. Clear / Cloudy)">

            </div>

            <textarea name="notes" rows="3" placeholder="Notes (optional)"></textarea>

            <button type="submit" name="add_record" class="btn-primary">
                💾 Save Record
            </button>
        </form>
    </div>

    <!-- ================= HISTORY ================= -->
    <div class="card">
        <h3>📊 Water Quality History</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Pond</th>
                    <th>Date</th>
                    <th>Temp</th>
                    <th>pH</th>
                    <th>Oxygen</th>
                    <th>Ammonia</th>
                    <th>Turbidity</th>
                    <th>Notes</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($records): ?>
                <?php foreach($records as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['pond_name']) ?></td>
                        <td><?= date('d M Y H:i', strtotime($r['recorded_at'])) ?></td>

                        <td><?= $r['water_temp'] ?> °C</td>

                        <td>
                            <?= $r['ph_level'] ?>
                            <?php if ($r['ph_level'] < 6.5 || $r['ph_level'] > 8.5): ?>
                                ⚠
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= $r['dissolved_oxygen'] ?> mg/L
                            <?php if ($r['dissolved_oxygen'] < 3): ?>
                                🚨
                            <?php endif; ?>
                        </td>

                        <td><?= $r['ammonia'] ?> mg/L</td>

                        <td><?= htmlspecialchars($r['turbidity'] ?? '-') ?></td>

                        <td><?= htmlspecialchars($r['notes'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:#6b7280;">
                        No records found
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>