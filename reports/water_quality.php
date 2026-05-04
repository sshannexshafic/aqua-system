<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$pond_id = intval($_GET['pond_id'] ?? 0);
$ponds   = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();

$growth_data = [];
$feed_data   = [];
if ($pond_id) {
    $growth_data = $db->query("
        SELECT species, quantity, avg_weight, stocking_date,
               DATEDIFF(CURDATE(), stocking_date) AS days_in_pond
        FROM fish_stocks WHERE pond_id = $pond_id ORDER BY stocking_date
    ")->fetchAll();

    $feed_data = $db->query("
        SELECT feed_type, SUM(quantity) AS total_qty, SUM(cost) AS total_cost,
               MIN(feed_date) AS first_date, MAX(feed_date) AS last_date
        FROM feed_records WHERE pond_id = $pond_id
        GROUP BY feed_type ORDER BY total_qty DESC
    ")->fetchAll();
}
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">📈</span>
        <h1>Fish Growth Report</h1>
    </div>

    <!-- Pond Selector -->
    <div class="card">
        <form method="GET" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
            <select name="pond_id" required style="padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;min-width:220px;">
                <option value="">-- Select a Pond --</option>
                <?php foreach($ponds as $p): ?>
                <option value="<?php echo $p['id'];?>" <?php if($p['id']==$pond_id)echo 'selected';?>><?php echo htmlspecialchars($p['name']);?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">📊 View Report</button>
        </form>
    </div>

    <?php if ($pond_id && $growth_data): ?>
    <!-- Fish Stock Summary -->
    <div class="card">
        <h3>🐟 Fish Stock Details</h3>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Species</th><th>Quantity</th><th>Avg Weight (kg)</th><th>Stocking Date</th><th>Days in Pond</th><th>Est. Total Weight (kg)</th></tr></thead>
                <tbody>
                <?php foreach($growth_data as $g):
                    $total_wt = $g['quantity'] * $g['avg_weight'];
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($g['species']); ?></strong></td>
                    <td class="number"><?php echo number_format($g['quantity']); ?></td>
                    <td class="number"><?php echo number_format($g['avg_weight'],3); ?></td>
                    <td><?php echo date('d M Y', strtotime($g['stocking_date'])); ?></td>
                    <td><?php echo $g['days_in_pond']; ?> days</td>
                    <td class="number"><?php echo number_format($total_wt, 2); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Feed Summary -->
    <?php if ($feed_data): ?>
    <div class="card">
        <h3>🥬 Feed Usage Summary</h3>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Feed Type</th><th>Total Qty (kg)</th><th>Total Cost (UGX)</th><th>First Used</th><th>Last Used</th></tr></thead>
                <tbody>
                <?php
                $total_feed = 0; $total_feed_cost = 0;
                foreach($feed_data as $f):
                    $total_feed += $f['total_qty'];
                    $total_feed_cost += $f['total_cost'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['feed_type']); ?></td>
                    <td class="number"><?php echo number_format($f['total_qty'],2); ?></td>
                    <td class="number">UGX <?php echo number_format($f['total_cost']); ?></td>
                    <td><?php echo date('d M Y', strtotime($f['first_date'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($f['last_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:#f0fdf4;font-weight:700;">
                    <td>TOTAL</td>
                    <td class="number"><?php echo number_format($total_feed,2); ?></td>
                    <td class="number">UGX <?php echo number_format($total_feed_cost); ?></td>
                    <td colspan="2"></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($pond_id): ?>
    <div class="card"><p style="text-align:center;color:#6b7280;padding:2rem;">No stock data for this pond yet.</p></div>
    <?php endif; ?>

    <div class="button-group">
        <button onclick="window.print()" class="btn-secondary">🖨️ Print</button>
        <a href="../admin/reports.php" class="btn-primary">← Back to Reports</a>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$ponds = $db->query("
    SELECT p.*, u.username AS farmer_name,
           (SELECT COUNT(*) FROM fish_stocks WHERE pond_id=p.id) AS stock_count,
           (SELECT COALESCE(SUM(quantity),0) FROM fish_stocks WHERE pond_id=p.id) AS total_fish,
           (SELECT COUNT(*) FROM water_quality WHERE pond_id=p.id) AS wq_readings,
           (SELECT COALESCE(SUM(total_revenue),0) FROM harvest_records WHERE pond_id=p.id) AS revenue,
           (SELECT COUNT(*) FROM health_records WHERE pond_id=p.id AND status='open') AS open_health
    FROM ponds p
    LEFT JOIN users u ON p.farmer_id = u.id
    ORDER BY p.name
")->fetchAll();

$status_colors = ['active'=>'#10b981','inactive'=>'#9ca3af','under_maintenance'=>'#f59e0b'];
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">🏞️</span>
        <h1>Pond Summary Report</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card blue"><h3><?php echo count($ponds); ?></h3><p>Total Ponds</p></div>
        <div class="stat-card green">
            <h3><?php echo count(array_filter($ponds, fn($p)=>$p['status']==='active')); ?></h3>
            <p>Active Ponds</p>
        </div>
        <div class="stat-card orange">
            <h3>UGX <?php echo number_format(array_sum(array_column($ponds,'revenue'))); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>

    <div class="card">
        <h3>📋 All Ponds Overview</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Pond Name</th><th>Farmer</th><th>Size (m²)</th><th>Status</th><th>Fish Count</th><th>WQ Readings</th><th>Revenue (UGX)</th><th>Open Cases</th></tr>
                </thead>
                <tbody>
                <?php foreach($ponds as $p):
                    $sc = $status_colors[$p['status']] ?? '#6b7280';
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                        <small style="color:#6b7280;"><?php echo htmlspecialchars($p['location'] ?? ''); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($p['farmer_name'] ?? 'Unassigned'); ?></td>
                    <td class="number"><?php echo number_format($p['size'],0); ?></td>
                    <td><span class="role-badge" style="background:<?php echo $sc; ?>20;color:<?php echo $sc; ?>;"><?php echo ucwords(str_replace('_',' ',$p['status'])); ?></span></td>
                    <td class="number"><?php echo number_format($p['total_fish']); ?></td>
                    <td class="number"><?php echo $p['wq_readings']; ?></td>
                    <td class="number">UGX <?php echo number_format($p['revenue']); ?></td>
                    <td>
                        <?php if($p['open_health']>0): ?>
                        <span class="role-badge" style="background:#fef2f2;color:#dc2626;"><?php echo $p['open_health']; ?> Open</span>
                        <?php else: ?><span style="color:#10b981;">✅ Clear</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
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

<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$pond_id = intval($_GET['pond_id'] ?? 0);
$days    = intval($_GET['days']    ?? 30);
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
    $records = $db->query("
        SELECT w.*, p.name AS pond_name
        FROM water_quality w
        JOIN ponds p ON w.pond_id = p.id
        WHERE w.recorded_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        ORDER BY w.recorded_at DESC LIMIT 100
    ")->fetchAll();
}

$critical = array_filter($records, function($r){ return $r['ph_level']<6.5||$r['ph_level']>8.5||$r['dissolved_oxygen']<4; });
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">💧</span>
        <h1>Water Quality Report</h1>
    </div>

    <div class="card">
        <form method="GET" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
            <select name="pond_id" style="padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;min-width:200px;">
                <option value="0">All Ponds</option>
                <?php foreach($ponds as $p): ?>
                <option value="<?php echo $p['id'];?>" <?php if($p['id']==$pond_id)echo 'selected';?>><?php echo htmlspecialchars($p['name']);?></option>
                <?php endforeach; ?>
            </select>
            <select name="days" style="padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;">
                
                <option value="<?php echo $d;?>" <?php if($d==$days)echo 'selected';?>><?php echo $label;?></option>
                
            </select>
            <button type="submit" class="btn-primary">🔍 Filter</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card blue"><h3><?php echo count($records); ?></h3><p>Total Readings</p></div>
        <div class="stat-card" style="border-top-color:#ef4444;">
            <h3 style="color:#ef4444;"><?php echo count($critical); ?></h3>
            <p>Critical Alerts</p>
        </div>
        <div class="stat-card green">
            <h3><?php echo $records ? number_format(array_sum(array_column($records,'ph_level'))/count($records),2) : '—'; ?></h3>
            <p>Avg pH</p>
        </div>
        <div class="stat-card orange">
            <h3><?php echo $records ? number_format(array_sum(array_column($records,'water_temp'))/count($records),1) : '—'; ?>°C</h3>
            <p>Avg Temperature</p>
        </div>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <h3 style="margin:0;">📊 Water Quality Readings</h3>
            <input type="text" data-table-search="wqTable" placeholder="🔍 Search..."
                style="padding:.6rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.9rem;">
        </div>
        <div class="table-container">
            <table class="data-table" id="wqTable">
                <thead><tr><th>Pond</th><th>pH</th><th>Temp (°C)</th><th>DO (mg/L)</th><th>Status</th><th>Recorded</th></tr></thead>
                <tbody>
                <?php if ($records): foreach($records as $r):
                    $ph_ok = $r['ph_level']>=6.5 && $r['ph_level']<=8.5;
                    $do_ok = $r['dissolved_oxygen']>=4;
                    $temp_ok = $r['water_temp']>=18 && $r['water_temp']<=32;
                    $is_critical = !$ph_ok || !$do_ok;
                    $status = $is_critical ? 'Critical' : (!$temp_ok ? 'Warning' : 'Good');
                    $sc = $is_critical ? '#ef4444' : (!$temp_ok ? '#f59e0b' : '#10b981');
                ?>
                <tr style="<?php echo $is_critical?'background:#fef2f2;':''; ?>">
                    <td><?php echo htmlspecialchars($r['pond_name']); ?></td>
                    <td style="color:<?php echo $ph_ok?'inherit':'#ef4444';?>;font-weight:<?php echo $ph_ok?'400':'700';?>">
                        <?php echo $r['ph_level']; ?>
                    </td>
                    <td style="color:<?php echo $temp_ok?'inherit':'#f59e0b';?>"><?php echo $r['water_temp']; ?></td>
                    <td style="color:<?php echo $do_ok?'inherit':'#ef4444';?>;font-weight:<?php echo $do_ok?'400':'700';?>">
                        <?php echo $r['dissolved_oxygen']; ?>
                    </td>
                    <td><span class="role-badge" style="background:<?php echo $sc;?>20;color:<?php echo $sc;?>;"><?php echo $status;?></span></td>
                    <td><?php echo date('d M Y H:i', strtotime($r['recorded_at'])); ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#6b7280;padding:2rem;">No data for the selected period.</td></tr>
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
