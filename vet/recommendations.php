
<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('vet');

$database = new Database();
$db = $database->getConnection();

$message = ''; $error = '';

if ($_POST && isset($_POST['add_rec'])) {
    try {
        $farmer_stmt = $db->prepare("SELECT farmer_id FROM ponds WHERE id=?");
        $farmer_stmt->execute([$_POST['pond_id']]);
        $farmer_id = $farmer_stmt->fetch()['farmer_id'] ?? null;

        $stmt = $db->prepare("
            INSERT INTO vet_recommendations (pond_id, vet_id, farmer_id, recommendation, priority, category, due_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $due = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $stmt->execute([
            $_POST['pond_id'], $_SESSION['user_id'], $farmer_id,
            $_POST['recommendation'], $_POST['priority'], $_POST['category'], $due
        ]);
        $message = 'Recommendation sent to farmer!';
    } catch (Exception $e) { $error = $e->getMessage(); }
}

if ($_POST && isset($_POST['update_status'])) {
    $completed = $_POST['new_status'] === 'completed' ? date('Y-m-d H:i:s') : null;
    $stmt = $db->prepare("UPDATE vet_recommendations SET status=?, completed_at=? WHERE id=?");
    $stmt->execute([$_POST['new_status'], $completed, $_POST['rec_id']]);
    $message = 'Status updated.';
}

if ($_POST && isset($_POST['delete_rec'])) {
    $db->prepare("DELETE FROM vet_recommendations WHERE id=?")->execute([$_POST['rec_id']]);
    $message = 'Recommendation deleted.';
}

$ponds = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();
$recs  = $db->query("
    SELECT r.*, p.name AS pond_name, u.username AS farmer_name
    FROM vet_recommendations r
    JOIN ponds p ON r.pond_id = p.id
    LEFT JOIN users u ON r.farmer_id = u.id
    ORDER BY FIELD(r.priority,'urgent','high','medium','low'), r.created_at DESC
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">📋</span>
        <h1>Vet Recommendations</h1>
    </div>

    <?php if ($message): ?><div class="success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Add Recommendation -->
    <div class="card">
        <h3>✏️ New Recommendation</h3>
        <form method="POST">
            <div class="form-grid">
                <select name="pond_id" required>
                    <option value="">-- Select Pond --</option>
                    <?php foreach($ponds as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="priority" required>
                    <option value="medium">Medium Priority</option>
                    <option value="low">Low Priority</option>
                    <option value="high">High Priority</option>
                    <option value="urgent">🚨 Urgent</option>
                </select>
                <select name="category" required>
                    <option value="general">General</option>
                    <option value="feeding">Feeding</option>
                    <option value="water_quality">Water Quality</option>
                    <option value="health">Health / Disease</option>
                    <option value="stocking">Stocking</option>
                </select>
                <input type="date" name="due_date" placeholder="Due date (optional)">
            </div>
            <div style="margin-bottom:1.5rem;">
                <textarea name="recommendation" rows="4" required
                    placeholder="Write your recommendation for the farmer..."
                    style="width:100%;padding:1rem;border:2px solid #e5e7eb;border-radius:12px;font-size:1rem;resize:vertical;"></textarea>
            </div>
            <button type="submit" name="add_rec" class="btn-primary">📤 Send Recommendation</button>
        </form>
    </div>

    <!-- Recommendations List -->
    <div class="card">
        <h3>📬 All Recommendations</h3>
        <?php if ($recs): foreach($recs as $r):
            $pri_colors = ['urgent'=>'#ef4444','high'=>'#f59e0b','medium'=>'#3b82f6','low'=>'#10b981'];
            $st_colors  = ['pending'=>'#f59e0b','acknowledged'=>'#3b82f6','completed'=>'#10b981','dismissed'=>'#9ca3af'];
            $pc = $pri_colors[$r['priority']] ?? '#6b7280';
            $sc = $st_colors[$r['status']] ?? '#6b7280';
        ?>
        <div style="border:1px solid #e5e7eb;border-radius:16px;padding:1.5rem;margin-bottom:1rem;border-left:4px solid <?php echo $pc; ?>;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
                <div>
                    <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:.5rem;">
                        <strong><?php echo htmlspecialchars($r['pond_name']); ?></strong>
                        <span class="role-badge" style="background:<?php echo $pc; ?>20;color:<?php echo $pc; ?>;">
                            <?php echo strtoupper($r['priority']); ?>
                        </span>
                        <span class="role-badge" style="background:#f3f4f6;color:#374151;">
                            <?php echo ucfirst($r['category']); ?>
                        </span>
                        <?php if($r['farmer_name']): ?>
                        <span style="color:#6b7280;font-size:.85rem;">→ <?php echo htmlspecialchars($r['farmer_name']); ?></span>
                        <?php endif; ?>
                    </div>
                    <p style="margin:.5rem 0;color:#374151;"><?php echo nl2br(htmlspecialchars($r['recommendation'])); ?></p>
                    <small style="color:#9ca3af;">
                        Posted: <?php echo date('d M Y', strtotime($r['created_at'])); ?>
                        <?php if($r['due_date']): ?> · Due: <?php echo date('d M Y', strtotime($r['due_date'])); ?><?php endif; ?>
                        <?php if($r['completed_at']): ?> · Completed: <?php echo date('d M Y', strtotime($r['completed_at'])); ?><?php endif; ?>
                    </small>
                </div>
                <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                    <span class="role-badge" style="background:<?php echo $sc; ?>20;color:<?php echo $sc; ?>;">
                        <?php echo ucfirst($r['status']); ?>
                    </span>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="rec_id" value="<?php echo $r['id']; ?>">
                        <select name="new_status" onchange="this.form.submit()"
                            style="padding:.4rem;border:1px solid #e5e7eb;border-radius:8px;font-size:.85rem;">
                            <?php foreach(['pending','acknowledged','completed','dismissed'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php if($r['status']==$s)echo 'selected';?>><?php echo ucfirst($s);?></option>
                            <?php endforeach; ?>
                        </select>
                        <button name="update_status" style="display:none;"></button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="rec_id" value="<?php echo $r['id']; ?>">
                        <button type="submit" name="delete_rec" class="btn-danger btn-sm"
                            data-confirm="Delete this recommendation?">🗑️</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; else: ?>
            <p style="text-align:center;color:#6b7280;padding:2rem;">No recommendations yet. Use the form above.</p>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
PHPEOF