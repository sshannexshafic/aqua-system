
<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('vet');

$database = new Database();
$db = $database->getConnection();
$vet_id = $_SESSION['user_id'];

$message = ''; $error = '';

// Handle form submission
if ($_POST && isset($_POST['add_record'])) {
    try {
        $stmt = $db->prepare("
            INSERT INTO health_records 
            (pond_id, vet_id, visit_date, diagnosis, treatment, medication, severity, follow_up_date, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $follow_up = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

        if ($stmt->execute([
            $_POST['pond_id'], 
            $vet_id, 
            $_POST['visit_date'],
            $_POST['diagnosis'], 
            $_POST['treatment'], 
            $_POST['medication'],
            $_POST['severity'], 
            $follow_up, 
            $_POST['status'], 
            $_POST['notes']
        ])) {

            // ✅ SMART RECOMMENDATION SYSTEM
            $recommendations = [];

            // Rule 1: Severity check
            if ($_POST['severity'] === 'critical') {
                $recommendations[] = "🚨 Immediate vet action required + isolate pond";
            }

            // Rule 2: Diagnosis keyword detection
            if (stripos($_POST['diagnosis'], 'white spot') !== false) {
                $recommendations[] = "💊 Salt treatment recommended (5–10g/L)";
            }

            if (stripos($_POST['diagnosis'], 'fungal') !== false) {
                $recommendations[] = "🧪 Apply antifungal treatment";
            }

            if (stripos($_POST['diagnosis'], 'bacterial') !== false) {
                $recommendations[] = "💉 Use appropriate antibiotics";
            }

            // Combine recommendations
            if (!empty($recommendations)) {
                $message = "Health record added! | Recommendations: " . implode(" | ", $recommendations);
            } else {
                $message = 'Health record added successfully!';
            }
        }

    } catch (Exception $e) {
        $error = 'Error saving record: ' . $e->getMessage();
    }
}

// Update status
if ($_POST && isset($_POST['update_status'])) {
    $stmt = $db->prepare("UPDATE health_records SET status=? WHERE id=?");
    $stmt->execute([$_POST['new_status'], $_POST['record_id']]);
    $message = 'Status updated!';
}

// Delete
if ($_POST && isset($_POST['delete_record'])) {
    $db->prepare("DELETE FROM health_records WHERE id=?")->execute([$_POST['record_id']]);
    $message = 'Record deleted.';
}

$ponds   = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();
$records = $db->query("
    SELECT h.*, p.name AS pond_name
    FROM health_records h
    JOIN ponds p ON h.pond_id = p.id
    ORDER BY h.visit_date DESC
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;"></span>
        <h1>Health Records</h1>
    </div>

    <?php if ($message): ?><div class="success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Add Record -->
    <div class="card">
        <h3>Add Health Record</h3>
        <form method="POST" class="water-quality-form">
            <div class="form-grid">
                <select name="pond_id" required>
                    <option value="">-- Select Pond --</option>
                    <?php foreach($ponds as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
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
                <input type="date" name="follow_up_date" placeholder="Follow-up date (optional)">
                <input type="text" name="medication" placeholder="Medication (optional)">
            </div>
            <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
                <textarea name="diagnosis" rows="3" placeholder="Diagnosis / Observations *" required
                    style="padding:1rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;resize:vertical;"></textarea>
                <textarea name="treatment" rows="2" placeholder="Treatment prescribed"
                    style="padding:1rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;resize:vertical;"></textarea>
                <textarea name="notes" rows="2" placeholder="Additional notes"
                    style="padding:1rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;resize:vertical;"></textarea>
            </div>
            <button type="submit" name="add_record" class="btn-primary">💾 Save Health Record</button>
        </form>
    </div>

    <!-- Records Table -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <h3 style="margin:0;">All Health Records</h3>
            <input type="text" data-table-search="healthTable" placeholder="🔍 Search records..."
                style="padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;width:250px;">
        </div>
        <div class="table-container">
            <table class="data-table" id="healthTable">
                <thead>
                    <tr><th>Pond</th><th>Visit Date</th><th>Diagnosis</th><th>Severity</th><th>Status</th><th>Follow-up</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if ($records): foreach($records as $r):
                    $sev_colors = ['normal'=>'#10b981','mild'=>'#06b6d4','moderate'=>'#f59e0b','severe'=>'#f97316','critical'=>'#ef4444'];
                    $st_colors  = ['open'=>'#ef4444','resolved'=>'#10b981','monitoring'=>'#f59e0b'];
                    $sc = $sev_colors[$r['severity']] ?? '#6b7280';
                    $stc = $st_colors[$r['status']] ?? '#6b7280';
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($r['pond_name']); ?></strong></td>
                    <td><?php echo date('d M Y', strtotime($r['visit_date'])); ?></td>
                    <td title="<?php echo htmlspecialchars($r['diagnosis']); ?>">
                        <?php echo htmlspecialchars(mb_substr($r['diagnosis'],0,50)) . (strlen($r['diagnosis'])>50?'...':''); ?>
                    </td>
                    <td><span class="role-badge" style="background:<?php echo $sc; ?>20;color:<?php echo $sc; ?>;"><?php echo ucfirst($r['severity']); ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="record_id" value="<?php echo $r['id']; ?>">
                            <select name="new_status" onchange="this.form.submit()" style="padding:.4rem;border-radius:8px;border:1px solid #e5e7eb;font-size:.85rem;background:<?php echo $stc; ?>15;color:<?php echo $stc; ?>;">
                                <?php foreach(['open','monitoring','resolved'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php if($r['status']==$s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button name="update_status" style="display:none;">Update</button>
                        </form>
                    </td>
                    <td><?php echo $r['follow_up_date'] ? date('d M Y',strtotime($r['follow_up_date'])) : '<span style="color:#9ca3af;">None</span>'; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="record_id" value="<?php echo $r['id']; ?>">
                            <button type="submit" name="delete_record" class="btn-danger btn-sm"
                                data-confirm="Delete this health record?">🗑️</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" style="text-align:center;color:#6b7280;padding:2rem;">No health records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

// In health_records.php after saving
$recommendations = [];
if ($_POST['severity'] === 'critical') {
    $recommendations[] = "Immediate vet visit + isolate pond";
}
if ($_POST['diagnosis'] contains 'white spot' or similar) {
    $recommendations[] = "Salt treatment recommended (5-10g/L)";
}