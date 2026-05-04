<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('vet');

$database = new Database();
$db = $database->getConnection();
$vet_id = $_SESSION['user_id'];

// Stats (Now using 'status' column)
$total_ponds      = $db->query("SELECT COUNT(*) as c FROM ponds WHERE status='active'")->fetch()['c'];
$open_cases       = $db->query("SELECT COUNT(*) as c FROM health_records WHERE status='open'")->fetch()['c'];
$pending_recs     = $db->query("SELECT COUNT(*) as c FROM vet_recommendations WHERE status='pending'")->fetch()['c'];
$deaths_this_month= $db->query("SELECT COALESCE(SUM(count),0) as c FROM mortality_records 
                                 WHERE MONTH(mortality_date)=MONTH(CURDATE()) 
                                 AND YEAR(mortality_date)=YEAR(CURDATE())")->fetch()['c'];

// Recent health records
$recent_health = $db->query("
    SELECT h.*, p.name AS pond_name
    FROM health_records h
    JOIN ponds p ON h.pond_id = p.id
    ORDER BY h.created_at DESC LIMIT 6
")->fetchAll();

// Critical water quality alerts (last 24h)
$alerts = $db->query("
    SELECT w.*, p.name AS pond_name
    FROM water_quality w
    JOIN ponds p ON w.pond_id = p.id
    WHERE (w.ph_level < 6.5 OR w.ph_level > 8.5 OR w.dissolved_oxygen < 4)
      AND w.recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY w.recorded_at DESC LIMIT 5
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">🩺</span>
        <div>
            <h1>Vet Dashboard</h1>
            <p style="color:#6b7280;margin:0;">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <h3><?php echo $total_ponds; ?></h3>
            <p>Active Ponds</p>
        </div>
        <div class="stat-card" style="border-top-color:#ef4444;">
            <h3 style="color:#ef4444;"><?php echo $open_cases; ?></h3>
            <p>Open Health Cases</p>
        </div>
        <div class="stat-card orange">
            <h3><?php echo $pending_recs; ?></h3>
            <p>Pending Recommendations</p>
        </div>
        <div class="stat-card" style="border-top-color:#8b5cf6;">
            <h3 style="color:#8b5cf6;"><?php echo $deaths_this_month; ?></h3>
            <p>Deaths This Month</p>
        </div>
    </div>

    <!-- Water Quality Alerts -->
    <?php if ($alerts): ?>
    <div class="card" style="border-left:4px solid #ef4444;">
        <h3>⚠️ Critical Water Quality Alerts (Last 24h)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Pond</th><th>pH</th><th>Temp (°C)</th><th>DO (mg/L)</th><th>Recorded</th></tr></thead>
                <tbody>
                <?php foreach($alerts as $a): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($a['pond_name']); ?></strong></td>
                    <td style="color:<?php echo ($a['ph_level']<6.5||$a['ph_level']>8.5)?'#ef4444':'inherit'; ?>">
                        <?php echo $a['ph_level']; ?>
                    </td>
                    <td><?php echo $a['water_temp']; ?></td>
                    <td style="color:<?php echo $a['dissolved_oxygen']<4?'#ef4444':'inherit'; ?>">
                        <?php echo $a['dissolved_oxygen']; ?>
                    </td>
                    <td><?php echo date('d M H:i', strtotime($a['recorded_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Health Cases -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h3>🏥 Recent Health Cases</h3>
            <a href="health_records.php" class="btn-primary btn-sm">View All</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Pond</th><th>Diagnosis</th><th>Severity</th><th>Status</th><th>Visit Date</th></tr></thead>
                <tbody>
                <?php if ($recent_health): ?>
                    <?php foreach($recent_health as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['pond_name']); ?></td>
                        <td><?php echo htmlspecialchars(mb_substr($r['diagnosis'],0,60)) . (strlen($r['diagnosis'])>60?'...':''); ?></td>
                        <td>
                            <?php
                            $sev_colors = ['normal'=>'#10b981','mild'=>'#06b6d4','moderate'=>'#f59e0b','severe'=>'#f97316','critical'=>'#ef4444'];
                            $sc = $sev_colors[$r['severity']] ?? '#6b7280';
                            ?>
                            <span class="role-badge" style="background:<?php echo $sc; ?>20;color:<?php echo $sc; ?>;">
                                <?php echo ucfirst($r['severity']); ?>
                            </span>
                        </td>
                        <td>
                            <?php $st_colors=['open'=>'#ef4444','resolved'=>'#10b981','monitoring'=>'#f59e0b']; $stc=$st_colors[$r['status']]??'#6b7280'; ?>
                            <span class="role-badge" style="background:<?php echo $stc; ?>20;color:<?php echo $stc; ?>;">
                                <?php echo ucfirst($r['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($r['visit_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:2rem;">No health records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="button-group">
        <a href="health_records.php" class="btn-primary">🏥 Health Records</a>
        <a href="mortality.php" class="btn-danger">💀 Mortality Records</a>
        <a href="recommendations.php" class="btn-secondary">📋 Recommendations</a>
        <a href="../admin/ponds.php" class="btn-secondary">🏞️ View Ponds</a>
    </div>
</div>
<?php include '../includes/footer.php'; ?>