<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Dashboard Stats
$total_ponds = $db->query("SELECT COUNT(*) as count FROM ponds")->fetch()['count'];
$total_fish = $db->query("SELECT SUM(quantity) as count FROM fish_stocks")->fetch()['count'] ?? 0;
$total_revenue = $db->query("SELECT SUM(total_revenue) as revenue FROM harvest_records")->fetch()['revenue'] ?? 0;
$avg_ph = $db->query("SELECT AVG(ph_level) as avg FROM water_quality")->fetch()['avg'] ?? 0;

// Recent Harvests (Last 30 days)
$recent_harvests = $db->query("
    SELECT hr.*, p.name as pond_name 
    FROM harvest_records hr 
    JOIN ponds p ON hr.pond_id = p.id 
    WHERE hr.harvest_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY hr.harvest_date DESC
")->fetchAll();

// Water Quality Summary
$water_summary = $db->query("
    SELECT 
        COUNT(*) as total_records,
        AVG(water_temp) as avg_temp,
        AVG(ph_level) as avg_ph,
        AVG(dissolved_oxygen) as avg_do
    FROM water_quality 
    WHERE recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
")->fetch();
?>

<?php include '../includes/header.php'; ?>
<div class="admin-page">
    <div class="page-header">
        <h1><i class="icon-reports"></i> Management Reports</h1>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <h3><?php echo $total_ponds; ?></h3>
            <p>Total Ponds</p>
        </div>
        <div class="stat-card green">
            <h3><?php echo number_format($total_fish); ?></h3>
            <p>Total Fish</p>
        </div>
        <div class="stat-card orange">
            <h3>UGX <?php echo number_format($total_revenue); ?></h3>
            <p>Total Revenue</p>
        </div>
        <div class="stat-card purple">
            <h3><?php echo round($avg_ph, 2); ?></h3>
            <p>Avg pH Level</p>
        </div>
    </div>

    <div class="reports-grid">
        <!-- Recent Harvests -->
        <div class="card">
            <h3>Recent Harvests (30 days)</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr><th>Pond</th><th>Quantity</th><th>Revenue</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_harvests as $harvest): ?>
                        <tr>
                            <td><?php echo $harvest['pond_name']; ?></td>
                            <td><?php echo number_format($harvest['quantity']); ?></td>
                            <td class="number">UGX <?php echo number_format($harvest['total_revenue']); ?></td>
                            <td><?php echo date('M j', strtotime($harvest['harvest_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Water Quality Summary -->
        <div class="card">
            <h3>Water Quality (Last 7 days)</h3>
            <div class="metrics-grid">
                <div class="metric">
                    <span class="value"><?php echo $water_summary['total_records']; ?></span>
                    <span>Records</span>
                </div>
                <div class="metric">
                    <span class="value"><?php echo round($water_summary['avg_temp'], 1); ?>°C</span>
                    <span>Avg Temp</span>
                </div>
                <div class="metric">
                    <span class="value"><?php echo round($water_summary['avg_ph'], 2); ?></span>
                    <span>Avg pH</span>
                </div>
                <div class="metric">
                    <span class="value"><?php echo round($water_summary['avg_do'], 2); ?> mg/L</span>
                    <span>Avg DO</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Reports -->
    <div class="card">
        <h3>Generate Reports</h3>
        <div class="button-group">
            <a href="../reports/pond_report.php" class="btn-secondary">📊 Pond Report</a>
            <a href="../reports/financial_summary.php" class="btn-secondary">💰 Financial Report</a>
            <a href="../reports/water_quality.php" class="btn-secondary">💧 Water Quality</a>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>