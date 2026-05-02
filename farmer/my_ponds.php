<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();
$farmer_id = $_SESSION['user_id'];

$message = '';

// Handle water quality update
if ($_POST && isset($_POST['update_water_quality'])) {
    $data = [
        $_POST['water_temp'],
        $_POST['ph_level'],
        $_POST['dissolved_oxygen'],
        $_POST['pond_id']
    ];
    
    $stmt = $db->prepare("
        INSERT INTO water_quality (water_temp, ph_level, dissolved_oxygen, pond_id) 
        VALUES (?, ?, ?, ?)
    ");
    
    if ($stmt->execute($data)) {
        $message = "Water quality updated successfully!";
        
        // Check critical levels and send SMS
        require_once '../config/database.php';
        if ($data[1] < 6.5 || $data[1] > 8.5) {
            DatabaseConfig::sendSMS(
                "🚨 CRITICAL: Pond {$_POST['pond_id']} pH = {$data[1]}. Check immediately!"
            );
        }
    }
}

// Fetch farmer's ponds with latest data
$ponds = $db->prepare("
    SELECT 
        p.*,
        (SELECT ph_level FROM water_quality WHERE pond_id = p.id ORDER BY recorded_at DESC LIMIT 1) as latest_ph,
        (SELECT water_temp FROM water_quality WHERE pond_id = p.id ORDER BY recorded_at DESC LIMIT 1) as latest_temp,
        (SELECT COUNT(*) FROM fish_stocks WHERE pond_id = p.id) as fish_count,
        (SELECT SUM(quantity) FROM fish_stocks WHERE pond_id = p.id) as total_fish
    FROM ponds p 
    WHERE p.farmer_id = ? 
    ORDER BY p.name
");
$ponds->execute([$farmer_id]);
$ponds = $ponds->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="farmer-page">
    <div class="page-header">
        <h1><i class="icon-ponds"></i> My Ponds (<?php echo count($ponds); ?>)</h1>
        <p>Monitor your fish ponds and water quality</p>
        <?php if ($message) echo "<div class='success'>$message</div>"; ?>
    </div>

    <?php foreach($ponds as $pond): ?>
    <div class="pond-card">
        <div class="pond-header">
            <h3><?php echo htmlspecialchars($pond['name']); ?></h3>
            <div class="pond-stats">
                <span class="stat"><?php echo $pond['size']; ?> m²</span>
                <span class="stat"><?php echo $pond['depth']; ?>m deep</span>
                <?php if ($pond['fish_count']): ?>
                <span class="stat highlight"><?php echo number_format($pond['total_fish']); ?> fish</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Water Quality Section -->
        <div class="water-quality-section">
            <h4>Latest Water Quality</h4>
            <?php if ($pond['latest_ph']): ?>
            <div class="quality-metrics">
                <div class="metric-item">
                    <span class="label">pH:</span>
                    <span class="value <?php echo ($pond['latest_ph'] < 6.5 || $pond['latest_ph'] > 8.5) ? 'critical' : 'good'; ?>">
                        <?php echo $pond['latest_ph']; ?>
                    </span>
                </div>
                <div class="metric-item">
                    <span class="label">Temp:</span>
                    <span class="value <?php echo ($pond['latest_temp'] < 20 || $pond['latest_temp'] > 30) ? 'warning' : 'good'; ?>">
                        <?php echo $pond['latest_temp']; ?>°C
                    </span>
                </div>
            </div>
            <?php else: ?>
            <p class="no-data">No water quality data yet. <a href="#update-form">Update now</a></p>
            <?php endif; ?>
        </div>

        <!-- Quick Update Form -->
        <div class="update-form" id="update-form">
            <h4>Update Water Quality</h4>
            <form method="POST" class="quick-form">
                <input type="hidden" name="pond_id" value="<?php echo $pond['id']; ?>">
                
                <div class="form-row">
                    <input type="number" step="0.1" name="water_temp" placeholder="Temp °C" required>
                    <input type="number" step="0.1" name="ph_level" placeholder="pH" required>
                    <input type="number" step="0.1" name="dissolved_oxygen" placeholder="DO mg/L" required>
                </div>
                
                <button type="submit" name="update_water_quality" class="btn-primary btn-sm">
                    Update Now
                </button>
            </form>
        </div>

        <!-- Quick Actions -->
        <div class="pond-actions">
            <a href="../admin/fish_stocks.php?pond=<?php echo $pond['id']; ?>" class="btn-secondary btn-sm">
                Fish Stocks
            </a>
            <a href="feed_records.php?pond=<?php echo $pond['id']; ?>" class="btn-secondary btn-sm">
                Feed
            </a>
            <a href="harvest.php?pond=<?php echo $pond['id']; ?>" class="btn-success btn-sm">
                Harvest
            </a>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($ponds)): ?>
    <div class="empty-state">
        <i class="icon-pond-empty"></i>
        <h3>No ponds assigned yet</h3>
        <p>Contact admin to assign ponds to your account</p>
    </div>
    <?php endif; ?>
</div>

<style>
.pond-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.pond-header h3 {
    font-size: 1.8rem;
    color: #1e40af;
    margin-bottom: 0.5rem;
}

.pond-stats {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.stat {
    background: #f1f5f9;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.stat.highlight {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.water-quality-section {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.5rem 0;
}

.metric-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.value.good { color: #10b981; font-weight: 600; }
.value.warning { color: #f59e0b; font-weight: 600; }
.value.critical { color: #ef4444; font-weight: 700; animation: pulse 2s infinite; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.quick-form .form-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.quick-form input {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
}

.pond-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    color: #6b7280;
}

.empty-state i { font-size: 4rem; color: #d1d5db; display: block; margin-bottom: 1rem; }
</style>
<?php include '../includes/footer.php'; ?>