<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

/* =========================
   DASHBOARD STATS (SAFE)
========================= */

$total_ponds = $db->query("
    SELECT COUNT(*) AS count FROM ponds
")->fetch()['count'] ?? 0;

$total_fish = $db->query("
    SELECT COALESCE(SUM(quantity), 0) AS count FROM fish_stocks
")->fetch()['count'] ?? 0;

$total_revenue = $db->query("
    SELECT COALESCE(SUM(total_revenue), 0) AS revenue FROM harvest_records
")->fetch()['revenue'] ?? 0;

$avg_ph = $db->query("
    SELECT COALESCE(AVG(ph_level), 0) AS avg FROM water_quality
")->fetch()['avg'] ?? 0;

/* =========================
   RECENT HARVESTS
========================= */

$recent_harvests = $db->query("
    SELECT hr.*, p.name AS pond_name
    FROM harvest_records hr
    JOIN ponds p ON hr.pond_id = p.id
    WHERE hr.harvest_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY hr.harvest_date DESC
")->fetchAll();

/* =========================
   WATER QUALITY SUMMARY
========================= */

$water_summary = $db->query("
    SELECT 
        COUNT(*) AS total_records,
        COALESCE(AVG(water_temp), 0) AS avg_temp,
        COALESCE(AVG(ph_level), 0) AS avg_ph,
        COALESCE(AVG(dissolved_oxygen), 0) AS avg_do
    FROM water_quality
    WHERE recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
")->fetch();

if (!$water_summary) {
    $water_summary = [
        'total_records' => 0,
        'avg_temp' => 0,
        'avg_ph' => 0,
        'avg_do' => 0
    ];
}

/* =========================
   WATER TREND
========================= */

$water_trend = $db->query("
    SELECT recorded_at, ph_level
    FROM water_quality
    WHERE recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY recorded_at ASC
")->fetchAll();

include '../includes/header.php';
?>

<div class="admin-page">

    <!-- HEADER -->
    <div class="page-header">
        <h1>📊 Management Reports</h1>
    </div>

    <!-- STATS -->
    <div class="stats-grid">

        <div class="stat-card blue">
            <h3><?php echo $total_ponds; ?></h3>
            <p>🐟 Total Ponds</p>
        </div>

        <div class="stat-card green">
            <h3><?php echo number_format($total_fish); ?></h3>
            <p>🐠 Total Fish</p>
        </div>

        <div class="stat-card orange">
            <h3>UGX <?php echo number_format($total_revenue); ?></h3>
            <p>💰 Total Revenue</p>
        </div>

        <div class="stat-card purple">
            <h3><?php echo round($avg_ph, 2); ?></h3>
            <p>⚗️ Avg pH Level</p>
        </div>

    </div>

    <!-- GRID -->
    <div class="reports-grid">

        <!-- HARVESTS -->
        <div class="card">
            <h3>🌾 Recent Harvests (30 days)</h3>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pond</th>
                            <th>Quantity</th>
                            <th>Revenue</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (count($recent_harvests) > 0): ?>
                            <?php foreach ($recent_harvests as $harvest): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($harvest['pond_name']); ?></td>
                                    <td><?php echo number_format($harvest['quantity']); ?></td>
                                    <td>UGX <?php echo number_format($harvest['total_revenue']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($harvest['harvest_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No harvest records found</td></tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- WATER QUALITY -->
        <div class="card">
            <h3>💧 Water Quality (Last 7 days)</h3>

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

        <!-- CHART -->
        <div class="card">
            <h3>📈 Water Quality Trend (pH)</h3>
            <canvas id="waterTrend"></canvas>
        </div>

    </div>

    <!-- EXPORT -->
    <div class="card">
        <h3>📄 Generate Reports</h3>

        <div class="button-group">
            <a href="../reports/pond_report.php" class="btn-secondary">📊 Pond Report</a>
            <a href="../reports/financial_summary.php" class="btn-secondary">💰 Financial Report</a>
            <a href="../reports/water_quality.php" class="btn-secondary">💧 Water Quality</a>
        </div>
    </div>

</div>

<!-- CHART -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('waterTrend');

const labels = <?php echo json_encode(array_map(
    fn($r) => date('M j', strtotime($r['recorded_at'] ?? '')),
    $water_trend
)); ?>;

const dataPoints = <?php echo json_encode(array_map(
    fn($r) => round($r['ph_level'] ?? 0, 2),
    $water_trend
)); ?>;

if (ctx && labels.length > 0) {
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'pH Level',
                data: dataPoints,
                borderColor: '#36a2eb',
                backgroundColor: 'rgba(54,162,235,0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: false }
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>