<?php
session_start(); // Ensure session is active for auth
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
    // Used prepared statements for security
    $stmt_growth = $db->prepare("
        SELECT species, quantity, avg_weight, stocking_date,
               DATEDIFF(CURDATE(), stocking_date) AS days_in_pond
        FROM fish_stocks WHERE pond_id = ? ORDER BY stocking_date
    ");
    $stmt_growth->execute([$pond_id]);
    $growth_data = $stmt_growth->fetchAll();

    $stmt_feed = $db->prepare("
        SELECT feed_type, SUM(quantity) AS total_qty, SUM(cost) AS total_cost,
               MIN(feed_date) AS first_date, MAX(feed_date) AS last_date
        FROM feed_records WHERE pond_id = ?
        GROUP BY feed_type ORDER BY total_qty DESC
    ");
    $stmt_feed->execute([$pond_id]);
    $feed_data = $stmt_feed->fetchAll();
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
                <option value="<?php echo $p['id'];?>" <?php if($p['id']==$pond_id) echo 'selected';?>>
                    <?php echo htmlspecialchars($p['name']);?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">📊 View Report</button>
        </form>
    </div>

    <?php if ($pond_id && $growth_data): ?>
    <div class="card">
        <h3>🐟 Fish Stock Details</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Species</th><th>Quantity</th><th>Avg Weight (kg)</th><th>Stocking Date</th><th>Days in Pond</th><th>Est. Total Weight (kg)</th></tr>
                </thead>
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

    <?php if ($feed_data): ?>
    <div class="card">
        <h3>🥬 Feed Usage Summary</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr><th>Feed Type</th><th>Total Qty (kg)</th><th>Total Cost (UGX)</th><th>First Used</th><th>Last Used</th></tr>
                </thead>
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