<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';
require_once '../includes/db_connection.php';

requireRole('farmer');

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

$message = '';
$error = '';

/*
----------------------------------
ADD MORTALITY RECORD (FIXED)
----------------------------------
*/
if ($_POST && isset($_POST['add_mortality'])) {

    $pond_id = $_POST['pond_id'] ?? null;
    $count = $_POST['count'] ?? 0;
    $cause = $_POST['cause'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($pond_id && $count > 0) {

        $stmt = $db->prepare("
            INSERT INTO mortality_records 
            (pond_id, reported_by, mortality_date, count, cause, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $pond_id,
            $user_id,
            $date,
            $count,
            $cause,
            $notes
        ]);

        $message = "✅ Mortality record saved successfully!";
    } else {
        $error = "❌ Please fill all required fields.";
    }
}

/*
----------------------------------
GET PONDS
----------------------------------
*/
$ponds = $db->prepare("
    SELECT id, name 
    FROM ponds 
    WHERE farmer_id = ?
");
$ponds->execute([$user_id]);
$ponds = $ponds->fetchAll(PDO::FETCH_ASSOC);

/*
----------------------------------
RECENT RECORDS (FIXED)
----------------------------------
*/
$stmt = $db->prepare("
    SELECT m.*, p.name AS pond_name
    FROM mortality_records m
    JOIN ponds p ON m.pond_id = p.id
    WHERE m.reported_by = ?
    ORDER BY m.mortality_date DESC
    LIMIT 10
");

$stmt->execute([$user_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container">

<h2>💀 Fish Mortality Records</h2>

<?php if ($message): ?>
    <div style="color:green"><?= $message ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="color:red"><?= $error ?></div>
<?php endif; ?>

<div class="card">
<form method="POST">

    <select name="pond_id" required>
        <option value="">Select Pond</option>
        <?php foreach ($ponds as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <input type="number" name="count" placeholder="Fish died count" required>

    <input type="text" name="cause" placeholder="Cause (optional)">

    <textarea name="notes" placeholder="Notes (optional)"></textarea>

    <input type="date" name="date" value="<?= date('Y-m-d') ?>">

    <button type="submit" name="add_mortality">Save</button>

</form>
</div>

<div class="card">
<h3>Recent Records</h3>

<table width="100%">
<tr>
    <th>Pond</th>
    <th>Count</th>
    <th>Cause</th>
    <th>Date</th>
</tr>

<?php foreach ($records as $r): ?>
<tr>
    <td><?= htmlspecialchars($r['pond_name']) ?></td>
    <td><?= $r['count'] ?></td>
    <td><?= htmlspecialchars($r['cause'] ?? '-') ?></td>
    <td><?= $r['mortality_date'] ?></td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>

<?php include '../includes/footer.php'; ?>