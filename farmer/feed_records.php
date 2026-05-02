<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();
$farmer_id = $_SESSION['user_id'];

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    $data = [
        $_POST['pond_id'],
        $_POST['feed_type'],
        $_POST['quantity'],
        $_POST['cost'],
        $_POST['feed_date']
    ];
    
    $stmt = $db->prepare("
        INSERT INTO feed_records (pond_id, feed_type, quantity, cost, feed_date) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute($data)) {
        $message = "Feed record saved successfully!";
        
        // Log activity
        require_once '../config/database.php';
        DatabaseConfig::logActivity($farmer_id, 'ADD_FEED_RECORD', $_POST['pond_id']);
    } else {
        $error = "Error saving feed record!";
    }
}

// Fetch farmer's ponds and feed records
$ponds = $db->prepare("SELECT id, name FROM ponds WHERE farmer_id = ? ORDER BY name");
$ponds->execute([$farmer_id]);
$ponds = $ponds->fetchAll();

$feed_records = $db->prepare("
    SELECT fr.*, p.name as pond_name 
    FROM feed_records fr 
    JOIN ponds p ON fr.pond_id = p.id 
    WHERE p.farmer_id = ? 
    ORDER BY fr.feed_date DESC, fr.id DESC
");
$feed_records->execute([$farmer_id]);
$feed_records = $feed_records->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="farmer-page">
    <div class="page-header">
        <h1><i class="icon-feed"></i> Feed Records</h1>
        <p>Track all feeding activities for your ponds</p>
        <?php if ($message) echo "<div class='success'>$message</div>"; ?>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    </div>

    <!-- Add Feed Record Form -->
    <div class="card">
        <h3><i class="icon-plus"></i> Record Feeding</h3>
        <form method="POST" class="form-grid">
            <select name="pond_id" required>
                <option value="">Select Your Pond</option>
                <?php foreach($ponds as $pond): ?>
                <option value="<?php echo $pond['id']; ?>">
                    <?php echo htmlspecialchars($pond['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="feed_type" placeholder="Feed Type (e.g. Tilapia pellets)" required>
            <input type="number" step="0.01" name="quantity" placeholder="Quantity (kg)" required>
            <input type="number" step="0.01" name="cost" placeholder="Cost (UGX)" required>
            <input type="date" name="feed_date" value="<?php echo date('Y-m-d'); ?>" required>
            
            <button type="submit" class="btn-primary">
                <i class="icon-save"></i> Save Record
            </button>
        </form>
    </div>

    <!-- Feed Records Table -->
    <div class="card">
        <h3><i class="icon-list"></i> Feeding History (<?php echo count($feed_records); ?> records)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pond</th>
                        <th>Feed Type</th>
                        <th>Quantity</th>
                        <th>Cost</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($feed_records as $record): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($record['pond_name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($record['feed_type']); ?></td>
                        <td class="number"><?php echo $record['quantity']; ?> kg</td>
                        <td class="number">
                            UGX <?php echo number_format($record['cost']); ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($record['feed_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($feed_records)): ?>
        <div class="summary-stats">
            <div class="stat-item">
                <strong>Total Feed:</strong> 
                <?php 
                $total_feed = array_sum(array_column($feed_records, 'quantity'));
                echo $total_feed . ' kg';
                ?>
            </div>
            <div class="stat-item">
                <strong>Total Cost:</strong> 
                UGX <?php 
                $total_cost = array_sum(array_column($feed_records, 'cost'));
                echo number_format($total_cost);
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>