<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$year  = intval($_GET['year']  ?? date('Y'));
$month = intval($_GET['month'] ?? 0);

$where  = "YEAR(harvest_date) = $year";
$fwhere = "YEAR(feed_date) = $year";
if ($month) {
    $where  .= " AND MONTH(harvest_date) = $month";
    $fwhere .= " AND MONTH(feed_date) = $month";
}

$revenue  = $db->query("SELECT COALESCE(SUM(total_revenue),0) as r FROM harvest_records WHERE $where")->fetch()['r'];
$feed_cost= $db->query("SELECT COALESCE(SUM(cost),0) as c FROM feed_records WHERE $fwhere")->fetch()['c'];
$profit   = $revenue - $feed_cost;

$monthly = $db->query("
    SELECT MONTH(harvest_date) as m, MONTHNAME(harvest_date) as mn,
           SUM(total_revenue) as revenue, SUM(quantity) as fish_sold,
           SUM(total_weight) as kg_sold
    FROM harvest_records WHERE YEAR(harvest_date)=$year
    GROUP BY MONTH(harvest_date), MONTHNAME(harvest_date)
    ORDER BY MONTH(harvest_date)
")->fetchAll();

$by_pond = $db->query("
    SELECT p.name, SUM(h.total_revenue) as revenue,
           SUM(h.quantity) as fish, SUM(h.total_weight) as kg
    FROM harvest_records h
    JOIN ponds p ON h.pond_id = p.id
    WHERE $where GROUP BY p.id, p.name ORDER BY revenue DESC
")->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">💰</span>
        <h1>Financial Summary</h1>
    </div>

    <!-- Filter -->
    <div class="card">
        <form method="GET" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
            <select name="year" style="padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;">
                <?php for($y=date('Y');$y>=2020;$y--): ?>
                <option value="<?php echo $y; ?>" <?php if($y==$year)echo 'selected';?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <select name="month" style="padding:.75rem 1rem;border:2px solid #e5e7eb;border-radius:10px;">
                <option value="0">All Months</option>
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?php echo $m;?>" <?php if($m==$month)echo 'selected';?>><?php echo date('F',mktime(0,0,0,$m,1)); ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn-primary">🔍 Filter</button>
            <a href="financial_summary.php" class="btn-secondary">↩ Reset</a>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card green">
            <h3>UGX <?php echo number_format($revenue); ?></h3>
            <p>Total Revenue</p>
        </div>
        <div class="stat-card" style="border-top-color:#ef4444;">
            <h3 style="color:#ef4444;">UGX <?php echo number_format($feed_cost); ?></h3>
            <p>Feed Costs</p>
        </div>
        <div class="stat-card" style="border-top-color:<?php echo $profit>=0?'#10b981':'#ef4444';?>;">
            <h3 style="color:<?php echo $profit>=0?'#10b981':'#ef4444';?>;">UGX <?php echo number_format(abs($profit)); ?></h3>
            <p><?php echo $profit >= 0 ? 'Net Profit' : 'Net Loss'; ?></p>
        </div>
        <div class="stat-card blue">
            <h3><?php echo $profit > 0 && $revenue > 0 ? number_format(($profit/$revenue)*100,1).'%' : '0%'; ?></h3>
            <p>Profit Margin</p>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <div class="card">
        <h3>📅 Monthly Revenue – <?php echo $year; ?></h3>
        <?php if ($monthly): ?>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Month</th><th>Fish Sold</th><th>Weight (kg)</th><th>Revenue (UGX)</th></tr></thead>
                <tbody>
                <?php foreach($monthly as $m): ?>
                <tr>
                    <td><?php echo $m['mn']; ?></td>
                    <td class="number"><?php echo number_format($m['fish_sold']); ?></td>
                    <td class="number"><?php echo number_format($m['kg_sold'],2); ?></td>
                    <td class="number">UGX <?php echo number_format($m['revenue']); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p style="color:#6b7280;text-align:center;padding:2rem;">No harvest data for <?php echo $year; ?>.</p>
        <?php endif; ?>
    </div>

    <!-- By Pond -->
    <div class="card">
        <h3>🏞️ Revenue by Pond</h3>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Pond</th><th>Fish Sold</th><th>Total Weight (kg)</th><th>Revenue (UGX)</th></tr></thead>
                <tbody>
                <?php if ($by_pond): foreach($by_pond as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['name']); ?></strong></td>
                    <td class="number"><?php echo number_format($p['fish']); ?></td>
                    <td class="number"><?php echo number_format($p['kg'],2); ?></td>
                    <td class="number">UGX <?php echo number_format($p['revenue']); ?></td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:2rem;">No data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="button-group">
        <button onclick="window.print()" class="btn-secondary">🖨️ Print Report</button>
        <a href="../admin/reports.php" class="btn-primary">← Back to Reports</a>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
