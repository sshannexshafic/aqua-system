<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();
$farmer_id = $_SESSION['user_id'];

// ==================== STATS ====================
$total_ponds = 0;
$active_ponds = 0;
$total_stock = 0;

try {
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM ponds WHERE farmer_id = ?");
    $stmt->execute([$farmer_id]);
    $total_ponds = $stmt->fetch()['c'];

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM ponds WHERE farmer_id = ? AND status = 'active'");
    $stmt->execute([$farmer_id]);
    $active_ponds = $stmt->fetch()['c'];

    $stmt = $db->prepare("SELECT COALESCE(SUM(current_stock), 0) as total FROM ponds WHERE farmer_id = ?");
    $stmt->execute([$farmer_id]);
    $total_stock = $stmt->fetch()['total'];
} catch(Exception $e) {}

// ==================== FISH STOCK OVERVIEW ====================
$stock_overview = [];
try {
    $stmt = $db->prepare("
        SELECT name, current_stock, capacity 
        FROM ponds 
        WHERE farmer_id = ? 
        ORDER BY current_stock DESC LIMIT 6
    ");
    $stmt->execute([$farmer_id]);
    $stock_overview = $stmt->fetchAll();
} catch(Exception $e) {}

// ==================== HARVEST SUMMARY (Safe) ====================
$harvest_summary = [];
try {
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(harvest_date, '%Y-%m') as month,
            SUM(quantity_kg) as total_kg,
            SUM(total_value) as total_value
        FROM harvest_records 
        WHERE farmer_id = ?
        GROUP BY month
        ORDER BY month DESC LIMIT 6
    ");
    $stmt->execute([$farmer_id]);
    $harvest_summary = $stmt->fetchAll();
} catch(Exception $e) {}

// ==================== CRITICAL ALERTS ====================
$alerts = [];
try {
    $stmt = $db->prepare("
        SELECT p.name AS pond_name, w.ph_level, w.water_temp, w.dissolved_oxygen, w.recorded_at
        FROM water_quality w
        JOIN ponds p ON w.pond_id = p.id
        WHERE p.farmer_id = ?
          AND (w.ph_level < 6.5 OR w.ph_level > 8.5 OR w.dissolved_oxygen < 4.0)
        ORDER BY w.recorded_at DESC LIMIT 5
    ");
    $stmt->execute([$farmer_id]);
    $alerts = $stmt->fetchAll();
} catch(Exception $e) {}

// ==================== RECENT WATER QUALITY ====================
$water_quality = [];
try {
    $stmt = $db->prepare("
        SELECT p.name, w.ph_level, w.water_temp, w.dissolved_oxygen, w.recorded_at
        FROM water_quality w
        JOIN ponds p ON w.pond_id = p.id
        WHERE p.farmer_id = ?
        ORDER BY w.recorded_at DESC LIMIT 5
    ");
    $stmt->execute([$farmer_id]);
    $water_quality = $stmt->fetchAll();
} catch(Exception $e) {}

// ==================== RECENT HEALTH RECORDS ====================
$health_records = [];
try {
    $stmt = $db->prepare("
        SELECT h.*, p.name AS pond_name
        FROM health_records h
        JOIN ponds p ON h.pond_id = p.id
        WHERE p.farmer_id = ?
        ORDER BY h.visit_date DESC LIMIT 5
    ");
    $stmt->execute([$farmer_id]);
    $health_records = $stmt->fetchAll();
} catch(Exception $e) {}
?>

<?php include '../includes/header.php'; ?>

<div class="farmer-dashboard">

    <div class="page-header">
        <h1>🌾 Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
        <p>Here's an overview of your aquaculture farm</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🏞️</div>
            <h3><?= $total_ponds ?></h3>
            <p>Total Ponds</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <h3><?= $active_ponds ?></h3>
            <p>Active Ponds</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🐟</div>
            <h3><?= number_format($total_stock) ?></h3>
            <p>Total Stock (kg)</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <h3><?= count($harvest_summary) > 0 ? number_format(array_sum(array_column($harvest_summary, 'total_kg'))) : '0' ?></h3>
            <p>Harvested (kg)</p>
        </div>
    </div>

    <!-- Critical Alerts -->
    <?php if (!empty($alerts)): ?>
    <div class="card alert-card">
        <h3>⚠️ Critical Water Quality Alerts</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Pond</th><th>pH</th><th>Temp (°C)</th><th>DO</th><th>Time</th></tr>
                </thead>
                <tbody>
                    <?php foreach($alerts as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['pond_name']) ?></strong></td>
                        <td class="text-danger"><?= $a['ph_level'] ?></td>
                        <td><?= $a['water_temp'] ?></td>
                        <td class="text-danger"><?= $a['dissolved_oxygen'] ?></td>
                        <td><?= date('d M H:i', strtotime($a['recorded_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="dashboard-grid">

        <!-- Fish Stock Overview -->
        <div class="card">
            <h3>🐟 Fish Stock Overview</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr><th>Pond</th><th>Current Stock</th><th>Capacity</th><th>Utilization</th></tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($stock_overview)): ?>
                            <?php foreach($stock_overview as $pond): ?>
                            <tr>
                                <td><?= htmlspecialchars($pond['name']) ?></td>
                                <td><strong><?= number_format($pond['current_stock']) ?> kg</strong></td>
                                <td><?= number_format($pond['capacity']) ?> kg</td>
                                <td>
                                    <?php 
                                    $perc = $pond['capacity'] > 0 ? round(($pond['current_stock'] / $pond['capacity']) * 100) : 0;
                                    $color = ($perc > 85) ? '#ef4444' : ($perc > 60 ? '#f59e0b' : '#10b981');
                                    ?>
                                    <span style="color:<?= $color ?>; font-weight:600;"><?= $perc ?>%</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding:2rem;">No pond data yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Harvest Trend -->
        <div class="card">
            <h3>📈 Monthly Harvest Trend</h3>
            <div style="position:relative; height:320px;">
                <canvas id="harvestChart"></canvas>
            </div>
        </div>

        <!-- Recent Water Quality -->
        <div class="card">
            <h3>💧 Recent Water Quality</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Pond</th><th>pH</th><th>Temp</th><th>DO</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach($water_quality as $w): ?>
                        <tr>
                            <td><?= htmlspecialchars($w['name']) ?></td>
                            <td><?= $w['ph_level'] ?></td>
                            <td><?= $w['water_temp'] ?>°C</td>
                            <td><?= $w['dissolved_oxygen'] ?></td>
                            <td><?= date('d M', strtotime($w['recorded_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($water_quality)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:2rem;color:#888;">No records yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Health -->
        <div class="card">
            <h3>🏥 Recent Health Records</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Pond</th><th>Diagnosis</th><th>Severity</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($health_records as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['pond_name']) ?></td>
                            <td><?= htmlspecialchars(mb_substr($h['diagnosis'] ?? '', 0, 40)) ?>...</td>
                            <td><?= ucfirst($h['severity'] ?? '') ?></td>
                            <td><?= ucfirst($h['status'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($health_records)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:2rem;color:#888;">No health records yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="ponds.php" class="action-btn">🏞️ My Ponds</a>
        <a href="feed_management.php" class="action-btn">🐟 Feed Management</a>
        <a href="water_quality.php" class="action-btn">💧 Water Quality</a>
        <a href="health_records.php" class="action-btn">🏥 Health Records</a>
        <a href="harvest.php" class="action-btn">🌾 Harvest</a>
        <a href="mortality.php" class="action-btn">💀 Mortality</a>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('harvestChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(function($m){ return date('M Y', strtotime($m['month'].'-01')); }, array_reverse($harvest_summary))) ?>,
        datasets: [{
            label: 'Harvested (kg)',
            data: <?= json_encode(array_map(function($m){ return round($m['total_kg']); }, array_reverse($harvest_summary))) ?>,
            backgroundColor: '#10b981'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }}
    }
});
</script>

<style>
.farmer-dashboard { padding: 1.5rem; max-width: 1400px; margin: 0 auto; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.2rem; margin-bottom: 2rem; }
.stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); text-align: center; }
.stat-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
.dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 1.5rem; }
.quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-top: 2rem; }
.action-btn { padding: 1.25rem; background: white; border-radius: 12px; text-align: center; text-decoration: none; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.2s; }
.action-btn:hover { transform: translateY(-4px); }
.alert-card { border-left: 5px solid #ef4444; }
</style>

<?php include '../includes/footer.php'; ?>