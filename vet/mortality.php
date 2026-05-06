<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('vet');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

/* =========================
   ADD MORTALITY RECORD
========================= */
if ($_POST && isset($_POST['add_mortality'])) {
    try {
        $count = floatval($_POST['count']);
        $weight = floatval($_POST['estimated_weight']);
        $price = floatval($_POST['price_per_kg']);

        $loss = round($count * $weight * $price);

        $stmt = $db->prepare("
            INSERT INTO mortality_records 
            (pond_id, reported_by, mortality_date, count, estimated_weight, cause, probable_reason, action_taken, loss_value, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([
            $_POST['pond_id'],
            $_SESSION['user_id'],
            $_POST['mortality_date'],
            $count,
            $weight,
            $_POST['cause'],
            $_POST['probable_reason'],
            $_POST['action_taken'],
            $loss,
            $_POST['notes']
        ])) {

            /* =========================
               SMART RECOMMENDATIONS
            ========================= */
            $recommendations = [];

            // High mortality detection
            if ($count > 50) {
                $recommendations[] = "🚨 High mortality detected – investigate immediately";
            }

            // Based on probable reason
            if ($_POST['probable_reason'] === 'disease') {
                $recommendations[] = "💊 Possible disease outbreak – isolate pond and consult vet";
            }

            if ($_POST['probable_reason'] === 'poor_water_quality') {
                $recommendations[] = "💧 Check water parameters (pH, oxygen, temperature)";
            }

            if ($_POST['probable_reason'] === 'oxygen_depletion') {
                $recommendations[] = "🌬️ Increase aeration immediately";
            }

            // Cause keyword detection
            if (!empty($_POST['cause']) && stripos($_POST['cause'], 'white spot') !== false) {
                $recommendations[] = "🧂 Salt treatment recommended (5–10g/L)";
            }

            if (!empty($_POST['cause']) && stripos($_POST['cause'], 'fungal') !== false) {
                $recommendations[] = "🧪 Apply antifungal treatment";
            }

            if (!empty($_POST['cause']) && stripos($_POST['cause'], 'bacterial') !== false) {
                $recommendations[] = "💉 Use appropriate antibiotics";
            }

            // Final message
            if (!empty($recommendations)) {
                $message = "Mortality record saved.\n\nRecommendations:\n- " . implode("\n- ", $recommendations);
            } else {
                $message = "Mortality record saved successfully!";
            }
        }

    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

/* =========================
   DELETE RECORD
========================= */
if ($_POST && isset($_POST['delete_mortality'])) {
    $db->prepare("DELETE FROM mortality_records WHERE id=?")
       ->execute([$_POST['record_id']]);
    $message = "Record deleted successfully!";
}

/* =========================
   FETCH DATA
========================= */
$ponds = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();

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

    <h1>💀 Mortality Records</h1>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="success" style="white-space:pre-line;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo number_format($total_deaths); ?></h3>
            <p>Total Deaths</p>
        </div>

        <div class="stat-card">
            <h3>UGX <?php echo number_format($total_loss); ?></h3>
            <p>Total Loss</p>
        </div>
    </div>

    <!-- Add Form -->
    <div class="card">
        <h3>Add Mortality Record</h3>

        <form method="POST">
            <select name="pond_id" required>
                <option value="">Select Pond</option>
                <?php foreach ($ponds as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="mortality_date" value="<?= date('Y-m-d') ?>" required>
            <input type="number" name="count" placeholder="Fish died" required>
            <input type="number" step="0.01" name="estimated_weight" placeholder="Weight per fish (kg)">
            <input type="number" name="price_per_kg" placeholder="Price per kg (UGX)">

            <select name="probable_reason">
                <option value="unknown">Unknown</option>
                <option value="disease">Disease</option>
                <option value="poor_water_quality">Poor Water Quality</option>
                <option value="oxygen_depletion">Oxygen Depletion</option>
                <option value="predation">Predation</option>
            </select>

            <input type="text" name="cause" placeholder="Specific cause (e.g. white spot)">
            <textarea name="action_taken" placeholder="Action taken"></textarea>
            <textarea name="notes" placeholder="Notes"></textarea>

            <button type="submit" name="add_mortality">Save</button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="card">
        <h3>All Records</h3>

        <table border="1" width="100%">
            <tr>
                <th>Pond</th>
                <th>Date</th>
                <th>Count</th>
                <th>Loss</th>
                <th>Action</th>
            </tr>

            <?php foreach ($records as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['pond_name']) ?></td>
                <td><?= $r['mortality_date'] ?></td>
                <td><?= $r['count'] ?></td>
                <td>UGX <?= number_format($r['loss_value']) ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="record_id" value="<?= $r['id'] ?>">
                        <button name="delete_mortality">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>

        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>