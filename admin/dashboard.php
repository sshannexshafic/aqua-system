cat > /home/claude/aquaculturesystem_final/admin/dashboard.php << 'PHPEOF'
<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$total_ponds   = $db->query("SELECT COUNT(*) as c FROM ponds")->fetch()['c'];
$active_ponds  = $db->query("SELECT COUNT(*) as c FROM ponds WHERE status='active'")->fetch()['c'];
$total_fish    = $db->query("SELECT COALESCE(SUM(quantity),0) as c FROM fish_stocks")->fetch()['c'];
$total_revenue = $db->query("SELECT COALESCE(SUM(total_revenue),0) as r FROM harvest_records")->fetch()['r'];
$total_users   = $db->query("SELECT COUNT(*) as c FROM users")->fetch()['c'];
$open_health   = $db->query("SELECT COUNT(*) as c FROM health_records WHERE status='open'")->fetch()['c'] ?? 0;

$recent_wq = $db->query("
    SELECT w.*, p.name AS pond_name
    FROM water_quality w JOIN ponds p ON w.pond_id = p.id
    ORDER BY w.recorded_at DESC LIMIT 5
")->fetchAll();

$recent_harvest = $db->query("
    SELECT h.*, p.name AS pond_name
    FROM harvest_records h JOIN ponds p ON h.pond_id = p.id
    ORDER BY h.harvest_date DESC LIMIT 5
")->fetchAll();

$monthly_rev = $db->query("
    SELECT MONTHNAME(harvest_date) as m, SUM(total_revenue) as rev
    FROM harvest_records
    WHERE YEAR(harvest_date) = YEAR(CURDATE())
    GROUP BY MONTH(harvest_date) ORDER BY MONTH(harvest_date)
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">🏠</span>
        <div>
            <h1>Admin Dashboard</h1>
            <p style="color:#6b7280;margin:0;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?> · <?php echo date('l, d F Y'); ?></p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <h3><?php echo $total_ponds; ?></h3>
            <p>Total Ponds (<?php echo $active_ponds; ?> Active)</p>
        </div>
        <div class="stat-card green">
            <h3><?php echo number_format($total_fish); ?></h3>
            <p>Total Fish in Stock</p>
        </div>
        <div class="stat-card orange">
            <h3>UGX <?php echo number_format($total_revenue); ?></h3>
            <p>Total Revenue</p>
        </div>
        <div class="stat-card purple">
            <h3><?php echo $total_users; ?></h3>
            <p>System Users</p>
        </div>
    </div>

    <?php if ($open_health > 0): ?>
    <div class="error" style="margin-bottom:1.5rem;">
        ⚠️ There <?php echo $open_health==1?'is':'are'; ?> <strong><?php echo $open_health; ?> open health case<?php echo $open_health!=1?'s':''; ?></strong> requiring attention.
        <a href="../vet/health_records.php" style="color:inherit;font-weight:700;"> View →</a>
    </div>
    <?php endif; ?>

    <div class="reports-grid">
        <!-- Recent Water Quality -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h3>💧 Recent Water Quality</h3>
                <a href="../reports/water_quality.php" class="btn-primary btn-sm">View All</a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Pond</th><th>pH</th><th>Temp</th><th>DO</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if ($recent_wq): foreach($recent_wq as $r):
                        $alert = $r['ph_level']<6.5||$r['ph_level']>8.5||$r['dissolved_oxygen']<4;
                    ?>
                    <tr style="<?php echo $alert?'background:#fef2f2;':''; ?>">
                        <td><?php echo htmlspecialchars($r['pond_name']); ?></td>
                        <td style="color:<?php echo ($r['ph_level']<6.5||$r['ph_level']>8.5)?'#ef4444':'inherit'; ?>"><?php echo $r['ph_level']; ?></td>
                        <td><?php echo $r['water_temp']; ?>°C</td>
                        <td style="color:<?php echo $r['dissolved_oxygen']<4?'#ef4444':'inherit'; ?>"><?php echo $r['dissolved_oxygen']; ?></td>
                        <td><?php echo date('d M', strtotime($r['recorded_at'])); ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:1.5rem;">No readings yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3>⚡ Quick Actions</h3>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
                <a href="ponds.php" class="btn-primary" style="text-align:center;">🏞️ Manage Ponds</a>
                <a href="fish_stocks.php" class="btn-primary" style="text-align:center;background:linear-gradient(135deg,#10b981,#059669);">🐟 Fish Stocks</a>
                <a href="users.php" class="btn-secondary" style="text-align:center;">👥 Manage Users</a>
                <a href="reports.php" class="btn-secondary" style="text-align:center;">📊 Reports</a>
                <a href="../reports/financial_summary.php" class="btn-secondary" style="text-align:center;">💰 Financial Summary</a>
            </div>
        </div>
    </div>

    <!-- Recent Harvests -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3>🌾 Recent Harvests</h3>
            <a href="../reports/financial_summary.php" class="btn-primary btn-sm">View All</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Pond</th><th>Fish</th><th>Weight (kg)</th><th>Revenue (UGX)</th><th>Date</th></tr></thead>
                <tbody>
                <?php if ($recent_harvest): foreach($recent_harvest as $h): ?>
                <tr>
                    <td><?php echo htmlspecialchars($h['pond_name']); ?></td>
                    <td class="number"><?php echo number_format($h['quantity']); ?></td>
                    <td class="number"><?php echo number_format($h['total_weight'],2); ?></td>
                    <td class="number">UGX <?php echo number_format($h['total_revenue']); ?></td>
                    <td><?php echo date('d M Y', strtotime($h['harvest_date'])); ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:1.5rem;">No harvests recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
PHPEOF