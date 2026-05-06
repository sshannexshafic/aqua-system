<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Initialize variables
$total_revenue = 0;
$total_cost = 0;
$net_profit = 0;
$total_harvests = 0;
$total_kg_harvested = 0;
$expense_count = 0;
$avg_price_per_kg = 0;

// Date filters
$date_range = $_GET['range'] ?? 'all'; // all, month, quarter, year
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build date filter
$date_condition = "";
if ($date_range == 'month') {
    $date_condition = "AND harvest_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
} elseif ($date_range == 'quarter') {
    $date_condition = "AND harvest_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
} elseif ($date_range == 'year') {
    $date_condition = "AND harvest_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)";
} elseif ($date_range == 'custom' && $start_date && $end_date) {
    $date_condition = "AND harvest_date BETWEEN '$start_date' AND '$end_date'";
}

// Get total revenue from harvests
try {
    $stmt = $db->query("SELECT COALESCE(SUM(total_value), 0) as total FROM harvest_records WHERE 1=1 $date_condition");
    $total_revenue = $stmt->fetchColumn() ?? 0;
} catch(PDOException $e) {
    $total_revenue = 0;
}

// Get total expenses (using amount column)
try {
    $stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE 1=1");
    $total_cost = $stmt->fetchColumn() ?? 0;
} catch(PDOException $e) {
    $total_cost = 0;
}

// Get harvest statistics
try {
    $stmt = $db->query("
        SELECT 
            COUNT(*) as harvest_count,
            COALESCE(SUM(quantity_kg), 0) as total_kg
        FROM harvest_records 
        WHERE 1=1 $date_condition
    ");
    $harvest_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_harvests = $harvest_stats['harvest_count'] ?? 0;
    $total_kg_harvested = $harvest_stats['total_kg'] ?? 0;
} catch(PDOException $e) {
    $total_harvests = 0;
    $total_kg_harvested = 0;
}

// Get expense count
try {
    $expense_count = $db->query("SELECT COUNT(*) FROM expenses")->fetchColumn() ?? 0;
} catch(PDOException $e) {
    $expense_count = 0;
}

// Calculate average price per kg
if ($total_kg_harvested > 0) {
    $avg_price_per_kg = $total_revenue / $total_kg_harvested;
}

// Calculate net profit
$net_profit = $total_revenue - $total_cost;
$profit_margin = ($total_revenue > 0) ? ($net_profit / $total_revenue) * 100 : 0;

// Get monthly breakdown for chart
$monthly_data = [];
try {
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(harvest_date, '%Y-%m') as month,
            COALESCE(SUM(total_value), 0) as revenue,
            COALESCE(SUM(quantity_kg), 0) as kg
        FROM harvest_records 
        WHERE harvest_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(harvest_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $monthly_data = [];
}

// Get recent expenses
$recent_expenses = [];
try {
    $stmt = $db->query("
        SELECT * FROM expenses 
        ORDER BY expense_date DESC 
        LIMIT 10
    ");
    $recent_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $recent_expenses = [];
}

// Get expenses by category
$expenses_by_category = [];
try {
    $stmt = $db->query("
        SELECT 
            category,
            COALESCE(SUM(amount), 0) as total
        FROM expenses 
        GROUP BY category
        ORDER BY total DESC
    ");
    $expenses_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $expenses_by_category = [];
}

// Get top harvests
$top_harvests = [];
try {
    $stmt = $db->query("
        SELECT h.*, p.name as pond_name
        FROM harvest_records h
        JOIN ponds p ON h.pond_id = p.id
        ORDER BY h.total_value DESC
        LIMIT 5
    ");
    $top_harvests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $top_harvests = [];
}
?>

<?php include '../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Summary - Aquaculture System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .financial-dashboard {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .page-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .page-header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.primary {
            border-left: 4px solid #3498db;
        }
        
        .stat-card.success {
            border-left: 4px solid #27ae60;
        }
        
        .stat-card.danger {
            border-left: 4px solid #e74c3c;
        }
        
        .stat-card.warning {
            border-left: 4px solid #f39c12;
        }
        
        .stat-card h3 {
            font-size: 32px;
            margin: 10px 0;
            color: #2c3e50;
        }
        
        .stat-card p {
            color: #7f8c8d;
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 10px;
        }
        
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
            font-size: 12px;
        }
        
        .filter-group select, .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-card h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 18px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .amount-positive {
            color: #27ae60;
            font-weight: bold;
        }
        
        .amount-negative {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .export-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        @media print {
            .filter-bar, .export-buttons, .btn-secondary {
                display: none;
            }
        }
        
        .section-title {
            font-size: 20px;
            margin: 20px 0;
            color: #2c3e50;
        }
    </style>
</head>
<body>
<div class="financial-dashboard">
    <div class="page-header">
        <h1>📊 Financial Summary Report</h1>
        <p>Comprehensive financial overview of your aquaculture operations</p>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Date Range</label>
                <select name="range" onchange="this.form.submit()">
                    <option value="all" <?= $date_range == 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="month" <?= $date_range == 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="quarter" <?= $date_range == 'quarter' ? 'selected' : '' ?>>Last 90 Days</option>
                    <option value="year" <?= $date_range == 'year' ? 'selected' : '' ?>>Last Year</option>
                    <option value="custom" <?= $date_range == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                </select>
            </div>
            
            <?php if ($date_range == 'custom'): ?>
            <div class="filter-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= $start_date ?>">
            </div>
            <div class="filter-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?= $end_date ?>">
            </div>
            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn-primary">Apply Filter</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Export Buttons -->
    <div class="export-buttons">
        <button onclick="window.print()" class="btn-secondary">🖨️ Print Report</button>
        <button onclick="exportToCSV()" class="btn-secondary">📥 Export to CSV</button>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <p>Total Revenue</p>
            <h3>UGX <?= number_format($total_revenue, 2) ?></h3>
            <div class="stat-label">From <?= $total_harvests ?> harvests</div>
        </div>
        
        <div class="stat-card danger">
            <p>Total Expenses</p>
            <h3>UGX <?= number_format($total_cost, 2) ?></h3>
            <div class="stat-label"><?= $expense_count ?> transactions</div>
        </div>
        
        <div class="stat-card success">
            <p>Net Profit</p>
            <h3 class="<?= $net_profit >= 0 ? 'amount-positive' : 'amount-negative' ?>">
                UGX <?= number_format(abs($net_profit), 2) ?>
                <?= $net_profit >= 0 ? '▲' : '▼' ?>
            </h3>
            <div class="stat-label">Margin: <?= number_format($profit_margin, 1) ?>%</div>
        </div>
        
        <div class="stat-card warning">
            <p>Average Price/kg</p>
            <h3>UGX <?= number_format($avg_price_per_kg, 2) ?></h3>
            <div class="stat-label">Total: <?= number_format($total_kg_harvested, 2) ?> kg</div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Monthly Revenue Trend</h3>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <h3>Expenses by Category</h3>
            <div class="chart-container">
                <canvas id="expensesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Harvests -->
    <div class="chart-card">
        <h3>🏆 Top 5 Highest Value Harvests</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pond</th>
                    <th>Quantity (kg)</th>
                    <th>Total Value (UGX)</th>
                    <th>Buyer</th>
                    <th>Harvest Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_harvests)): ?>
                <tr><td colspan="5" style="text-align: center;">No harvest records found</td></tr>
                <?php else: ?>
                    <?php foreach ($top_harvests as $harvest): ?>
                    <tr>
                        <td><?= htmlspecialchars($harvest['pond_name']) ?></td>
                        <td><?= number_format($harvest['quantity_kg'], 2) ?></td>
                        <td class="amount-positive">UGX <?= number_format($harvest['total_value'], 2) ?></td>
                        <td><?= htmlspecialchars($harvest['buyer_name'] ?? '—') ?></td>
                        <td><?= date('d M Y', strtotime($harvest['harvest_date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Recent Expenses -->
    <div class="chart-card" style="margin-top: 20px;">
        <h3>💰 Recent Expenses</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount (UGX)</th>
                    <th>Payment Method</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_expenses)): ?>
                <tr><td colspan="5" style="text-align: center;">No expense records found</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_expenses as $expense): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($expense['expense_date'])) ?></td>
                        <td><?= htmlspecialchars($expense['category']) ?></td>
                        <td><?= htmlspecialchars(substr($expense['description'] ?? '', 0, 50)) ?></td>
                        <td class="amount-negative">UGX <?= number_format($expense['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($expense['payment_method'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Prepare data for charts
const monthlyData = <?= json_encode($monthly_data) ?>;

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [{
            label: 'Revenue (UGX)',
            data: monthlyData.map(item => item.revenue),
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }, {
            label: 'Quantity (kg)',
            data: monthlyData.map(item => item.kg),
            borderColor: '#27ae60',
            backgroundColor: 'rgba(39, 174, 96, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        let value = context.raw;
                        if (context.dataset.label.includes('Revenue')) {
                            return label + ': UGX ' + value.toLocaleString();
                        }
                        return label + ': ' + value.toLocaleString() + ' kg';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Revenue (UGX)'
                },
                ticks: {
                    callback: function(value) {
                        return 'UGX ' + value.toLocaleString();
                    }
                }
            },
            y1: {
                position: 'right',
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Quantity (kg)'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        }
    }
});

// Expenses by Category Chart
const expensesData = <?= json_encode($expenses_by_category) ?>;
const expensesCtx = document.getElementById('expensesChart').getContext('2d');
new Chart(expensesCtx, {
    type: 'pie',
    data: {
        labels: expensesData.map(item => item.category),
        datasets: [{
            data: expensesData.map(item => item.total),
            backgroundColor: [
                '#e74c3c',
                '#f39c12',
                '#3498db',
                '#27ae60',
                '#9b59b6',
                '#e67e22',
                '#1abc9c',
                '#34495e'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.raw;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: UGX ${value.toLocaleString()} (${percentage}%)`;
                    }
                }
            },
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Export to CSV function
function exportToCSV() {
    // Create CSV content
    let csv = "Financial Summary Report\n";
    csv += "Generated: " + new Date().toLocaleString() + "\n\n";
    
    csv += "Key Metrics\n";
    csv += "Total Revenue,UGX " + <?= $total_revenue ?> + "\n";
    csv += "Total Expenses,UGX " + <?= $total_cost ?> + "\n";
    csv += "Net Profit,UGX " + <?= $net_profit ?> + "\n";
    csv += "Total Harvests," + <?= $total_harvests ?> + "\n";
    csv += "Total KG Harvested," + <?= $total_kg_harvested ?> + "\n";
    csv += "Average Price per KG,UGX " + <?= $avg_price_per_kg ?> + "\n";
    csv += "Profit Margin," + <?= number_format($profit_margin, 2) ?> + "%\n\n";
    
    csv += "Monthly Breakdown\n";
    csv += "Month,Revenue (UGX),Quantity (kg)\n";
    <?php foreach ($monthly_data as $data): ?>
    csv += "<?= $data['month'] ?>,<?= $data['revenue'] ?>,<?= $data['kg'] ?>\n";
    <?php endforeach; ?>
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'financial_report_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>