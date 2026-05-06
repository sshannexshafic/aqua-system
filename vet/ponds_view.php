<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('vet');

$database = new Database();
$db = $database->getConnection();

$ponds = $db->query("
    SELECT p.*, u.username AS farmer_name,
           (SELECT COUNT(*) FROM fish_stocks fs WHERE fs.pond_id = p.id) AS stock_count,
           (SELECT COUNT(*) FROM health_records hr WHERE hr.pond_id = p.id AND hr.status='open') AS open_cases
    FROM ponds p
    LEFT JOIN users u ON p.farmer_id = u.id
    ORDER BY p.name
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">
    <div class="page-header">
        <span style="font-size:2.5rem;">🏞️</span>
        <div>
            <h1>Ponds Overview</h1>
            <p style="color:#6b7280;margin:0;">All ponds — read-only view for veterinary reference</p>
        </div>
    </div>

    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Pond Name</th><th>Farmer</th><th>Size (m²)</th><th>Status</th><th>Fish Stocks</th><th>Open Health Cases</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if ($ponds): foreach($ponds as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($p['farmer_name'] ?? '—'); ?></td>
                    <td><?php echo number_format($p['size_sqm'] ?? 0); ?></td>
                    <td>
                        <?php $sc = $p['status']==='active' ? '#10b981' : '#6b7280'; ?>
                        <span class="role-badge" style="background:<?php echo $sc; ?>20;color:<?php echo $sc; ?>;">
                            <?php echo ucfirst($p['status']); ?>
                        </span>
                    </td>
                    <td class="number"><?php echo $p['stock_count']; ?></td>
                    <td>
                        <?php if ($p['open_cases'] > 0): ?>
                            <span class="role-badge" style="background:#fef2f2;color:#ef4444;"><?php echo $p['open_cases']; ?> open</span>
                        <?php else: ?>
                            <span style="color:#10b981;">✓ None</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="health_records.php?pond_id=<?php echo $p['id']; ?>" class="btn-primary btn-sm">Health</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" style="text-align:center;color:#6b7280;padding:2rem;">No ponds found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
