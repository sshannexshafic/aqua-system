<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT COUNT(*) as total_ponds FROM ponds WHERE farmer_id = ?");
$stmt->execute([$farmer_id]);
$total_ponds = $stmt->fetch()['total_ponds'];
?>
<?php include '../includes/header.php'; ?>
<div class="dashboard">
    <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <h3><?php echo $total_ponds; ?></h3>
            <p>My Ponds</p>
        </div>
        <!-- More stats -->
    </div>

    <!-- Recent Water Quality -->
    <div class="card">
        <h3>Recent Water Quality</h3>
        <?php
        $stmt = $db->prepare("
            SELECT p.name, w.ph_level, w.water_temp, w.dissolved_oxygen 
            FROM water_quality w 
            JOIN ponds p ON w.pond_id = p.id 
            WHERE p.farmer_id = ? 
            ORDER BY w.recorded_at DESC LIMIT 5
        ");
        $stmt->execute([$farmer_id]);
        $records = $stmt->fetchAll();
        ?>
        <table class="data-table">
            <tr><th>Pond</th><th>pH</th><th>Temp(°C)</th><th>DO(mg/L)</th></tr>
            <?php foreach($records as $record): ?>
            <tr>
                <td><?php echo $record['name']; ?></td>
                <td><?php echo $record['ph_level']; ?></td>
                <td><?php echo $record['water_temp']; ?></td>
                <td><?php echo $record['dissolved_oxygen']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>