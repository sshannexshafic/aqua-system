<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$ponds = $db->query("
    SELECT p.*, u.username AS farmer_name,
           (SELECT COALESCE(SUM(fs.quantity),0) FROM fish_stocks fs WHERE fs.pond_id=p.id) AS total_fish,
           (SELECT COUNT(*) FROM water_quality wq WHERE wq.pond_id=p.id) AS wq_readings,
           (SELECT COALESCE(SUM(h.total_revenue),0) FROM harvest_records h WHERE h.pond_id=p.id) AS revenue,
           (SELECT COUNT(*) FROM health_records hr WHERE hr.pond_id=p.id AND hr.status='open') AS open_cases
    FROM ponds p
    LEFT JOIN users u ON p.farmer_id = u.id
    ORDER BY p.name
")->fetchAll();

$status_colors = ['active'=>'#10b981','inactive'=>'#9ca3af','under_maintenance'=>'#f59e0b'];
$total_revenue = array_sum(array_column($ponds,'revenue'));
$active_count  = count(array_filter($ponds, fn($p)=>$p['status']==='active'));
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">🏞️</span>
        <div>
            <h1>Pond Summary Report</h1>
            <p style="color:#6b7280;margin:0;">Overview of all ponds and their performance</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card blue"><h3><?php echo count($ponds); ?></h3><p>Total Ponds</p></div>
        <div class="stat-card green"><h3><?php echo $active_count; ?></h3><p>Active Ponds</p></div>
        <div class="stat-card orange"><h3>UGX <?php echo number_format($total_revenue); ?></h3><p>Total Revenue</p></div>
        <div class="stat-card purple"><h3><?php echo count($ponds)-$active_count; ?></h3><p>Inactive / Maintenance</p></div>
    </div>

    <div class="card">
        <h3>📋 All Ponds</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Pond</th><th>Farmer</th><th>Size (m²)</th><th>Status</th><th>Fish</th><th>WQ Readings</th><th>Revenue (UGX)</th><th>Health</th></tr>
                </thead>
                <tbody>
                <?php if($ponds): foreach($ponds as $p):
                    $sc = $status_colors[$p['status']] ?? '#6b7280';
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                        <small style="color:#6b7280;"><?php echo htmlspecialchars($p['location'] ?? ''); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($p['farmer_name'] ?? '—'); ?></td>
                    <td class="number"><?php echo number_format($p['size'] ?? 0); ?></td>
                    <td>
                        <span class="role-badge" style="background:<?php echo $sc;?>20;color:<?php echo $sc;?>;">
                            <?php echo ucwords(str_replace('_',' ',$p['status'])); ?>
                        </span>
                    </td>
                    <td class="number"><?php echo number_format($p['total_fish']); ?></td>
                    <td class="number"><?php echo $p['wq_readings']; ?></td>
                    <td class="number">UGX <?php echo number_format($p['revenue']); ?></td>
                    <td>
                        <?php if($p['open_cases']>0): ?>
                            <span class="role-badge" style="background:#fef2f2;color:#ef4444;"><?php echo $p['open_cases']; ?> Open</span>
                        <?php else: ?>
                            <span style="color:#10b981;">✅ Clear</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" style="text-align:center;color:#6b7280;padding:2rem;">No ponds found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="button-group">
        <button onclick="window.print()" class="btn-secondary">🖨️ Print</button>
        <a href="../admin/reports.php" class="btn-primary">← Back to Reports</a>
    </div>
</div>
<?php include '../includes/footer.php'; ?>