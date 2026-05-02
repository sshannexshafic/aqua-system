<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();
$farmer_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Handle harvest recording
if ($_POST) {
    $data = [
        $_POST['pond_id'],
        $_POST['quantity'],
        $_POST['avg_weight'],
        $_POST['sale_price'],
        $_POST['quantity'] * $_POST['avg_weight'] * $_POST['sale_price'],
        $_POST['harvest_date']
    ];
    
    $stmt = $db->prepare("
        INSERT INTO harvest_records (pond_id, quantity, avg_weight, sale_price, total_revenue, harvest_date) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute($data)) {
        $message = "Harvest recorded! Revenue: UGX " . number_format($data[4]);
        
        // SMS notification to admin
        require_once '../config/database.php';
        DatabaseConfig::sendSMS(
            "New harvest recorded! Pond: {$_POST['pond_id']}, Revenue: UGX " . number_format($data[4])
        );
        
        DatabaseConfig::logActivity($farmer_id, 'RECORD_HARVEST', $data[4]);
    } else {
        $error = "Error recording harvest!";
    }
}

// Fetch ponds and harvests
$ponds = $db->prepare("SELECT id, name FROM ponds WHERE farmer_id = ?");
$ponds->execute([$farmer_id]);
$ponds = $ponds->fetchAll();

$harvests = $db->prepare("
    SELECT hr.*, p.name as pond_name 
    FROM harvest_records hr 
    JOIN ponds p ON hr.pond_id = p.id 
    WHERE p.farmer_id = ? 
    ORDER BY hr.harvest_date DESC
");
$harvests->execute([$farmer_id]);
$harvests = $harvests->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="farmer-page">
    <div class="page-header">
        <h1><i class="icon-harvest"></i> Harvest Records</h1>
        <p>Record your harvests and track revenue</p>
        <?php if ($message) echo "<div class='success'>$message</div>"; ?>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    </div>

    <!-- Record Harvest Form -->
    <div class="card">
        <h3><i class="icon-plus"></i> Record New Harvest</h3>
        <form method="POST" class="form-grid">
            <select name="pond_id" required>
                <option value="">Select Pond</option>
                <?php foreach($ponds as $pond): ?>
                <option value="<?php echo $pond['id']; ?>"><?php echo $pond['name']; ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="number" name="quantity" placeholder="Fish Harvested" required>
            <input type="number" step="0.01" name="avg_weight" placeholder="Avg Weight (kg)" required>
            <input type="number" step="0.01" name="sale_price" placeholder="Price per kg (UGX)" required>
            <input type="date" name="harvest_date" value="<?php echo date('Y-m-d'); ?>" required>
            
            <div class="form-row">
                <label>Estimated Revenue:</label>
                <div id="revenue-preview" class="revenue-display">UGX 0</div>
            </div>
            
            <button type="submit" class="btn-success">
                <i class="icon-save"></i> Record Harvest
            </button>
        </form>
    </div>

    <!-- Harvest History -->
    <div class="card">
        <h3><i class="icon-list"></i> Harvest History (<?php echo count($harvests); ?>)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pond</th>
                        <th>Fish</th>
                        <th>Avg Weight</th>
                        <th>Price/kg</th>
                        <th>Revenue</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($harvests as $harvest): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($harvest['pond_name']); ?></td>
                        <td class="number"><?php echo number_format($harvest['quantity']); ?></td>
                        <td><?php echo $harvest['avg_weight']; ?> kg</td>
                        <td>UGX <?php echo number_format($harvest['sale_price'], 0); ?></td>
                        <td class="highlight">
                            UGX <?php echo number_format($harvest['total_revenue']); ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($harvest['harvest_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($harvests)): ?>
        <div class="summary-stats highlight">
            <div class="stat-item large">
                <strong>Total Revenue:</strong> 
                UGX <?php 
                $total_revenue = array_sum(array_column($harvests, 'total_revenue'));
                echo number_format($total_revenue);
                ?>
            </div>
            <div class="stat-item">
                <strong>Total Fish Harvested:</strong> 
                <?php 
                $total_fish = array_sum(array_column($harvests, 'quantity'));
                echo number_format($total_fish);
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qty = document.querySelector('input[name="quantity"]');
    const weight = document.querySelector('input[name="avg_weight"]');
    const price = document.querySelector('input[name="sale_price"]');
    const preview = document.getElementById('revenue-preview');
    
    function calculateRevenue() {
        const q = parseFloat(qty.value) || 0;
        const w = parseFloat(weight.value) || 0;
        const p = parseFloat(price.value) || 0;
        const total = q * w * p;
        preview.textContent = 'UGX ' + total.toLocaleString();
    }
    
    qty.addEventListener('input', calculateRevenue);
    weight.addEventListener('input', calculateRevenue);
    price.addEventListener('input', calculateRevenue);
});
</script>
<?php include '../includes/footer.php'; ?>