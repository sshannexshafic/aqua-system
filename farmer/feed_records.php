<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();
$farmer_id = $_SESSION['user_id'];

$message = '';
$error = '';

// ==================== HANDLE PRICE UPDATE ====================
if (isset($_POST['update_price'])) {
    try {
        $feed_type = trim($_POST['feed_type']);
        $market_price = floatval($_POST['market_price']);

        if ($feed_type && $market_price > 0) {
            // Save to history
            $db->prepare("
                INSERT INTO feed_price_history (farmer_id, feed_type, old_price, new_price, changed_at)
                SELECT farmer_id, feed_type, price_per_kg, ?, NOW()
                FROM feed_market_prices 
                WHERE farmer_id = ? AND feed_type = ?
            ")->execute([$market_price, $farmer_id, $feed_type]);

            // Update current price
            $stmt = $db->prepare("
                INSERT INTO feed_market_prices (feed_type, price_per_kg, farmer_id, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE price_per_kg = VALUES(price_per_kg), updated_at = NOW()
            ");
            $stmt->execute([$feed_type, $market_price, $farmer_id]);

            $message = "✅ Market price for <strong>" . htmlspecialchars($feed_type) . "</strong> updated successfully!";
        }
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// ==================== HANDLE FEED RECORD ====================
if (isset($_POST['log_feed'])) {
    try {
        $quantity_kg = floatval($_POST['quantity_kg']);
        $cost_per_kg = floatval($_POST['cost_per_kg']);
        $total_cost  = $quantity_kg * $cost_per_kg;

        $stmt = $db->prepare("
            INSERT INTO feed_records 
            (pond_id, feed_date, feed_type, quantity_kg, cost_per_kg, total_cost, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['pond_id'],
            $_POST['feed_date'],
            trim($_POST['feed_type']),
            $quantity_kg,
            $cost_per_kg,
            $total_cost,
            trim($_POST['notes'] ?? '')
        ]);

        $message = "✅ Feed record saved successfully!";
    } catch (Exception $e) {
        $error = "❌ Error saving record: " . $e->getMessage();
    }
}

// ==================== FETCH DATA ====================
$ponds = $db->prepare("SELECT id, name FROM ponds WHERE farmer_id = ? ORDER BY name");
$ponds->execute([$farmer_id]);
$ponds = $ponds->fetchAll();

$market_prices = $db->prepare("
    SELECT feed_type, price_per_kg, updated_at 
    FROM feed_market_prices 
    WHERE farmer_id = ? ORDER BY feed_type
");
$market_prices->execute([$farmer_id]);
$market_prices = $market_prices->fetchAll();

$feed_records = $db->prepare("
    SELECT fr.*, p.name as pond_name 
    FROM feed_records fr 
    JOIN ponds p ON fr.pond_id = p.id 
    WHERE p.farmer_id = ? 
    ORDER BY fr.feed_date DESC, fr.id DESC
");
$feed_records->execute([$farmer_id]);
$feed_records = $feed_records->fetchAll();

// Chart Data - Last 12 Months
$chartData = $db->prepare("
    SELECT 
        DATE_FORMAT(feed_date, '%Y-%m') as month,
        SUM(quantity_kg) as total_kg,
        SUM(total_cost) as total_cost
    FROM feed_records fr
    JOIN ponds p ON fr.pond_id = p.id
    WHERE p.farmer_id = ?
    GROUP BY month
    ORDER BY month DESC LIMIT 12
");
$chartData->execute([$farmer_id]);
$monthly = $chartData->fetchAll();

$months = [];
$kg_data = [];
$cost_data = [];
foreach(array_reverse($monthly) as $m) {
    $months[] = date('M Y', strtotime($m['month'] . '-01'));
    $kg_data[] = round($m['total_kg'], 2);
    $cost_data[] = round($m['total_cost'], 0);
}
?>

<?php include '../includes/header.php'; ?>

<div class="farmer-page">

    <div class="page-header">
        <h1>🐟 Feed Management</h1>
        <p>Track feeding activities • Manage market prices • Monitor trends</p>
    </div>

    <?php if ($message): ?><div class="success"><?= $message ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>

    <!-- Trend Chart -->
    <div class="card">
        <h3>📈 Feed Usage & Cost Trend (Last 12 Months)</h3>
        <div style="position: relative; height: 380px; margin: 20px 0;">
            <canvas id="feedTrendChart"></canvas>
        </div>
    </div>

    <!-- Market Prices Management -->
    <div class="card">
        <h3>💰 Manage Market Prices</h3>
        
        <form method="POST" class="form-grid" style="margin-bottom: 25px;">
            <input type="text" name="feed_type" placeholder="Feed Type (e.g. Tilapia Pellets)" required>
            <input type="number" step="0.01" name="market_price" placeholder="Price per Kg (UGX)" required>
            <button type="submit" name="update_price" class="btn-primary">Update Price</button>
        </form>

        <div class="table-container">
            <table class="data-table" id="priceTable">
                <thead>
                    <tr>
                        <th>Feed Type</th>
                        <th>Current Price (UGX/kg)</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($market_prices as $mp): ?>
                    <tr data-feed="<?= htmlspecialchars($mp['feed_type']) ?>">
                        <td><strong><?= htmlspecialchars($mp['feed_type']) ?></strong></td>
                        <td>
                            <span class="current-price">UGX <?= number_format($mp['price_per_kg'], 2) ?></span>
                            <input type="number" step="0.01" class="edit-price-input" 
                                   value="<?= $mp['price_per_kg'] ?>" style="display:none; width:140px;">
                        </td>
                        <td><?= date('d M Y H:i', strtotime($mp['updated_at'])) ?></td>
                        <td>
                            <button class="btn-small edit-btn">Edit</button>
                            <button class="btn-small save-btn" style="display:none;">Save</button>
                            <button class="btn-small history-btn">History</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Record New Feed -->
    <div class="card">
        <h3>📝 Record New Feeding</h3>
        <form method="POST" class="form-grid" id="feedForm">
            <select name="pond_id" required>
                <option value="">-- Select Pond --</option>
                <?php foreach($ponds as $pond): ?>
                <option value="<?= $pond['id'] ?>"><?= htmlspecialchars($pond['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="feed_date" value="<?= date('Y-m-d') ?>" required>

            <select name="feed_type" id="feed_type" required onchange="autoFillPrice()">
                <option value="">-- Select Feed Type --</option>
                <?php foreach($market_prices as $mp): ?>
                <option value="<?= htmlspecialchars($mp['feed_type']) ?>" 
                        data-price="<?= $mp['price_per_kg'] ?>">
                    <?= htmlspecialchars($mp['feed_type']) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <div style="display:flex; gap:12px; align-items:center;">
                <input type="number" step="0.01" name="quantity_kg" id="quantity_kg" 
                       placeholder="Quantity (kg)" required style="flex:1;">
                <label style="white-space:nowrap;">
                    <input type="checkbox" id="use_market_price" checked> Use Market Price
                </label>
            </div>

            <input type="number" step="0.01" name="cost_per_kg" id="cost_per_kg" 
                   placeholder="Cost per Kg (UGX)" required>

            <textarea name="notes" rows="2" placeholder="Additional notes (optional)"></textarea>

            <button type="submit" name="log_feed" class="btn-primary btn-large">
                💾 Save Feed Record
            </button>
        </form>
    </div>

    <!-- Feeding History -->
    <div class="card">
        <h3>📋 Feeding History (<?= count($feed_records) ?> records)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pond</th>
                        <th>Feed Type</th>
                        <th>Qty (kg)</th>
                        <th>Cost/kg</th>
                        <th>Total Cost</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($feed_records as $r): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($r['feed_date'])) ?></td>
                        <td><strong><?= htmlspecialchars($r['pond_name']) ?></strong></td>
                        <td><?= htmlspecialchars($r['feed_type']) ?></td>
                        <td class="number"><?= number_format($r['quantity_kg'], 2) ?> kg</td>
                        <td class="number">UGX <?= number_format($r['cost_per_kg'], 2) ?></td>
                        <td class="number"><strong>UGX <?= number_format($r['total_cost']) ?></strong></td>
                        <td><?= htmlspecialchars($r['notes'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($feed_records)): ?>
        <div class="summary-stats">
            <strong>Total Feed Used:</strong> <?= number_format(array_sum(array_column($feed_records, 'quantity_kg')), 2) ?> kg &nbsp;&nbsp;&nbsp;
            <strong>Total Cost:</strong> UGX <?= number_format(array_sum(array_column($feed_records, 'total_cost'))) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Price History Modal -->
<div id="historyModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalFeedType">Price History</h3>
            <button onclick="closeModal()" class="btn-small">✕</button>
        </div>
        <div class="modal-body" id="historyContent">
            Loading...
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// ==================== CHART ====================
new Chart(document.getElementById('feedTrendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Feed Used (kg)',
                data: <?= json_encode($kg_data) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                yAxisID: 'y'
            },
            {
                label: 'Total Cost (UGX)',
                data: <?= json_encode($cost_data) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { position: 'left', title: { display: true, text: 'Quantity (kg)' }},
            y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Cost (UGX)' }}
        }
    }
});

// ==================== AUTO FILL PRICE ====================
function autoFillPrice() {
    const select = document.getElementById('feed_type');
    const priceInput = document.getElementById('cost_per_kg');
    const useMarket = document.getElementById('use_market_price').checked;

    const option = select.options[select.selectedIndex];
    const price = option.getAttribute('data-price');

    if (price && useMarket) {
        priceInput.value = parseFloat(price).toFixed(2);
        priceInput.readOnly = true;
    } else {
        priceInput.readOnly = false;
    }
}

document.getElementById('use_market_price').addEventListener('change', autoFillPrice);

// ==================== INLINE EDIT ====================
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        row.querySelector('.current-price').style.display = 'none';
        row.querySelector('.edit-price-input').style.display = 'inline-block';
        this.style.display = 'none';
        row.querySelector('.save-btn').style.display = 'inline-block';
    });
});

document.querySelectorAll('.save-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const feedType = row.getAttribute('data-feed');
        const newPrice = row.querySelector('.edit-price-input').value;

        if (newPrice > 0) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="feed_type" value="${feedType}">
                <input type="hidden" name="market_price" value="${newPrice}">
                <input type="hidden" name="update_price" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});

// ==================== PRICE HISTORY MODAL ====================
async function showHistory(feedType) {
    document.getElementById('modalFeedType').textContent = feedType + ' - Price History';
    document.getElementById('historyContent').innerHTML = '<p>Loading history...</p>';
    document.getElementById('historyModal').style.display = 'flex';

    try {
        const res = await fetch(`get_price_history.php?feed_type=${encodeURIComponent(feedType)}`);
        const html = await res.text();
        document.getElementById('historyContent').innerHTML = html;
    } catch(e) {
        document.getElementById('historyContent').innerHTML = '<p style="color:red;">Failed to load history.</p>';
    }
}

function closeModal() {
    document.getElementById('historyModal').style.display = 'none';
}

document.querySelectorAll('.history-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const feedType = row.getAttribute('data-feed');
        showHistory(feedType);
    });
});
</script>

<style>
.modal {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 1000;
}
.modal-content {
    background: white; border-radius: 12px; width: 90%; max-width: 620px; max-height: 85vh; overflow: auto;
}
.modal-header {
    padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;
}
</style>

<?php include '../includes/footer.php'; ?>