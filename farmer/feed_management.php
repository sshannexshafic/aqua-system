<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];

$message = '';
$error = '';

// ================= SAVE FEED RECORD =================
if ($_POST && isset($_POST['log_feed'])) {
    try {

        $quantity_kg = floatval($_POST['quantity_kg']);
        $cost_per_kg = floatval($_POST['cost_per_kg']);
        $total_cost = $quantity_kg * $cost_per_kg;

        $stmt = $db->prepare("
            INSERT INTO feed_records
            (pond_id, feed_date, feed_type, quantity_kg, cost_per_kg, total_cost, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['pond_id'],
            $_POST['feed_date'],
            $_POST['feed_type'],
            $quantity_kg,
            $cost_per_kg,
            $total_cost,
            $_POST['notes']
        ]);

        $message = "Feed record saved successfully!";

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

// ================= FEED RECORDS =================
$records = $db->prepare("
    SELECT f.*, p.name AS pond_name
    FROM feed_records f
    JOIN ponds p ON f.pond_id = p.id
    WHERE p.farmer_id = ?
    ORDER BY f.feed_date DESC
");
$records->execute([$farmer_id]);
$records = $records->fetchAll();

include '../includes/header.php';
?>

<div class="admin-page">

    <div class="page-header">
        <h1>🐟 Feed Management</h1>
        <p>Track feed usage and costs for your ponds</p>
    </div>

    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ================= FEED FORM ================= -->
    <div class="card">
        <h3>➕ Record Feed Usage</h3>

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

                <input type="date" name="feed_date" value="<?= date('Y-m-d') ?>" required>

                <input type="text" name="feed_type" placeholder="Feed Type (e.g. pellets)" required>

                <input type="number" step="0.01" name="quantity_kg" placeholder="Quantity (kg)" required>

                <input type="number" step="0.01" name="cost_per_kg" placeholder="Cost per kg (UGX)" required>

            </div>

            <textarea name="notes" rows="2" placeholder="Notes (optional)"></textarea>

            <button type="submit" name="log_feed" class="btn-primary">
                💾 Save Feed Record
            </button>
        </form>
    </div>

    <!-- ================= HISTORY ================= -->
    <div class="card">
        <h3>📋 Feed History</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Pond</th>
                    <th>Feed Type</th>
                    <th>Qty (kg)</th>
                    <th>Cost/kg</th>
                    <th>Total Cost</th>
                    <th>Notes</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($records): ?>
                <?php foreach($records as $r): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($r['feed_date'])) ?></td>
                        <td><?= htmlspecialchars($r['pond_name']) ?></td>
                        <td><?= htmlspecialchars($r['feed_type']) ?></td>

                        <td><?= number_format($r['quantity_kg'], 2) ?> kg</td>

                        <td>UGX <?= number_format($r['cost_per_kg'], 2) ?></td>

                        <td><strong>UGX <?= number_format($r['total_cost'], 2) ?></strong></td>

                        <td><?= htmlspecialchars($r['notes'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;color:#6b7280;">
                        No feed records found
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>