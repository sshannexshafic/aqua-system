<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';

// Allow both farmers and vets
if (!in_array($_SESSION['role'], ['farmer', 'vet'])) {
    die("Access denied");
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];

$message = '';
$error = '';

// ================= ADD HEALTH RECORD =================
if ($_POST && isset($_POST['add_record'])) {
    try {

        $stmt = $db->prepare("
            INSERT INTO health_records 
            (pond_id, vet_id, visit_date, diagnosis, treatment, medication, severity, follow_up_date, status, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $follow_up = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

        $stmt->execute([
            $_POST['pond_id'],
            $user_id, // works for both vet/farmer
            $_POST['visit_date'],
            $_POST['diagnosis'],
            $_POST['treatment'],
            $_POST['medication'],
            $_POST['severity'],
            $follow_up,
            $_POST['status'],
            $_POST['notes'],
            $user_id
        ]);

        // ================= SMART RECOMMENDATIONS =================
        $recommendations = [];

        if ($_POST['severity'] === 'critical') {
            $recommendations[] = "🚨 Immediate vet attention + isolate pond";
        }

        if (stripos($_POST['diagnosis'], 'white spot') !== false) {
            $recommendations[] = "💊 Salt treatment (5–10g/L)";
        }

        if (stripos($_POST['diagnosis'], 'fungal') !== false) {
            $recommendations[] = "🧪 Apply antifungal treatment";
        }

        if (stripos($_POST['diagnosis'], 'bacterial') !== false) {
            $recommendations[] = "💉 Use antibiotics as prescribed";
        }

        $message = !empty($recommendations)
            ? "Record saved! | " . implode(" | ", $recommendations)
            : "Health record added successfully!";

    } catch (Exception $e) {
        $error = "Error saving record: " . $e->getMessage();
    }
}

// ================= UPDATE STATUS =================
if ($_POST && isset($_POST['update_status'])) {
    $stmt = $db->prepare("UPDATE health_records SET status=? WHERE id=?");
    $stmt->execute([
        $_POST['new_status'],
        $_POST['record_id']
    ]);
    $message = "Status updated!";
}

// ================= DELETE =================
if ($_POST && isset($_POST['delete_record'])) {
    $stmt = $db->prepare("DELETE FROM health_records WHERE id=?");
    $stmt->execute([$_POST['record_id']]);
    $message = "Record deleted.";
}

// ================= FETCH PONDS =================
$ponds = $db->prepare("SELECT id, name FROM ponds ORDER BY name");
$ponds->execute();
$ponds = $ponds->fetchAll();

// ================= FETCH RECORDS (ROLE-SAFE) =================
$records = $db->prepare("
    SELECT h.*, p.name AS pond_name
    FROM health_records h
    JOIN ponds p ON h.pond_id = p.id
    WHERE h.created_by = ?
    ORDER BY h.visit_date DESC
");

$records->execute([$user_id]);
$records = $records->fetchAll();

include '../includes/header.php';
?>

<div class="admin-page">

    <div class="page-header">
        <h1>Health Records</h1>
        <p>Farmers & Vets can record fish health data</p>
    </div>

    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ADD FORM -->
    <div class="card">
        <h3>Add Health Record</h3>

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

                <input type="date" name="visit_date" value="<?= date('Y-m-d') ?>" required>

                <select name="severity" required>
                    <option value="normal">Normal</option>
                    <option value="mild">Mild</option>
                    <option value="moderate">Moderate</option>
                    <option value="severe">Severe</option>
                    <option value="critical">Critical</option>
                </select>

                <select name="status">
                    <option value="open">Open</option>
                    <option value="monitoring">Monitoring</option>
                    <option value="resolved">Resolved</option>
                </select>

                <input type="date" name="follow_up_date">
                <input type="text" name="medication" placeholder="Medication">

            </div>

            <textarea name="diagnosis" rows="3" placeholder="Diagnosis *" required></textarea>
            <textarea name="treatment" rows="2" placeholder="Treatment"></textarea>
            <textarea name="notes" rows="2" placeholder="Notes"></textarea>

            <button type="submit" name="add_record" class="btn-primary">
                💾 Save Record
            </button>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">
        <h3>Health Records</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Pond</th>
                    <th>Date</th>
                    <th>Diagnosis</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Follow-up</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach($records as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['pond_name']) ?></td>
                    <td><?= date('d M Y', strtotime($r['visit_date'])) ?></td>
                    <td><?= htmlspecialchars($r['diagnosis']) ?></td>
                    <td><?= ucfirst($r['severity']) ?></td>
                    <td><?= ucfirst($r['status']) ?></td>
                    <td>
                        <?= $r['follow_up_date'] ? date('d M Y', strtotime($r['follow_up_date'])) : '-' ?>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="record_id" value="<?= $r['id'] ?>">
                            <button name="delete_record" class="btn-danger">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>