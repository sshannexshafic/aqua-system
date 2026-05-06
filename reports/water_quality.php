<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$pond_id = intval($_GET['pond_id'] ?? 0);
$days    = intval($_GET['days'] ?? 30);
$ponds   = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();

$records = [];
if ($pond_id) {
    $stmt = $db->prepare("
        SELECT w.*, p.name AS pond_name
        FROM water_quality w
        JOIN ponds p ON w.pond_id = p.id
        WHERE w.pond_id = ? AND w.recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY w.recorded_at DESC
    ");
    $stmt->execute([$pond_id, $days]);
    $records = $stmt->fetchAll();
} else {
    $stmt = $db->prepare("
        SELECT w.*, p.name AS pond_name
        FROM water_quality w
        JOIN ponds p ON w.pond_id = p.id
        WHERE w.recorded_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY w.recorded_at DESC LIMIT 100
    ");
    $stmt->execute([$days]);
    $records = $stmt->fetchAll();
}

$critical = array_filter($records, fn($r) => $r['ph_level']<6.5 || $r['ph_level']>8.5 || $r['dissolved_oxygen']<4);
$avg_ph   = $records ? number_format(array_sum(array_column($records,'ph_level'))/count($records),2) : '—';
$avg_temp = $records ? number_format(array_sum(array_column($records,'water_temp'))/count($records),1).'°C' : '—';

$periods = [7=>'Last 7 Days', 30=>'Last 30 Days', 90=>'Last 3 Months', 365=>'Last Year'];
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">💧</span>
        <div>
            <h1>Water Quality Report</h1>
            <p style="color:#6b7280;margin:0;">Monitor pond water conditions and alerts</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <form method="GET" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
            <select name="pond_id" style="padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;min-width:200px;">
                <option value="0">All Ponds</option>
                <?php foreach($ponds as $p): ?>
                <option value="<?php echo $p['id'];?>" <?php if($p['id']==$pond_id)echo 'selected';?>>
                    <?php echo htmlspecialchars($p['name']);?>
                </option>
                <?php endforeach; ?>
            </select>
            <select name="days" style="padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;">
                <?php foreach($periods as $d => $label): ?>
                <option value="<?php echo $d;?>" <?php if($d==$days)echo 'selected';?>><?php echo $label;?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">🔍 Filter</button>
        </form>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue"><h3><?php echo count($records); ?></h3><p>Total Readings</p></div>
        <div class="stat-card" style="border-top:4px solid #ef4444;">
            <h3 style="color:#ef4444;"><?php echo count($critical); ?></h3>
            <p>Critical Alerts</p>
        </div>
        <div class="stat-card green"><h3><?php echo $avg_ph; ?></h3><p>Average pH</p></div>
        <div class="stat-card orange"><h3><?php echo $avg_temp; ?></h3><p>Average Temperature</p></div>
    </div>

    <!-- Table -->
    <div class="card">
        <h3>📊 Water Quality Readings</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Pond</th><th>pH</th><th>Temp (°C)</th><th>DO (mg/L)</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php if ($records): foreach($records as $r):
                    $ph_ok   = $r['ph_level'] >= 6.5 && $r['ph_level'] <= 8.5;
                    $do_ok   = $r['dissolved_oxygen'] >= 4;
                    $temp_ok = $r['water_temp'] >= 18 && $r['water_temp'] <= 32;
                    $critical_row = !$ph_ok || !$do_ok;
                    $status  = $critical_row ? 'Critical' : (!$temp_ok ? 'Warning' : 'Good');
                    $sc      = $critical_row ? '#ef4444' : (!$temp_ok ? '#f59e0b' : '#10b981');
                ?>
                <tr style="<?php echo $critical_row ? 'background:#fef2f2;' : ''; ?>">
                    <td><?php echo htmlspecialchars($r['pond_name']); ?></td>
                    <td style="color:<?php echo $ph_ok?'inherit':'#ef4444';?>;font-weight:<?php echo $ph_ok?'400':'700';?>">
                        <?php echo $r['ph_level']; ?>
                    </td>
                    <td style="color:<?php echo $temp_ok?'inherit':'#f59e0b';?>">
                        <?php echo $r['water_temp']; ?>
                    </td>
                    <td style="color:<?php echo $do_ok?'inherit':'#ef4444';?>;font-weight:<?php echo $do_ok?'400':'700';?>">
                        <?php echo $r['dissolved_oxygen']; ?>
                    </td>
                    <td>
                        <span class="role-badge" style="background:<?php echo $sc;?>20;color:<?php echo $sc;?>;">
                            <?php echo $status; ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y H:i', strtotime($r['recorded_at'])); ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#6b7280;padding:2rem;">No readings found for this period.</td></tr>
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