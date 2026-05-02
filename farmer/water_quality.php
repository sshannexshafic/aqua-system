<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();

if ($_POST) {
    $pond_id = $_POST['pond_id'];
    $data = [
        $_POST['water_temp'],
        $_POST['ph_level'],
        $_POST['dissolved_oxygen'],
        $pond_id
    ];
    
    $stmt = $db->prepare("INSERT INTO water_quality (water_temp, ph_level, dissolved_oxygen, pond_id) VALUES (?, ?, ?, ?)");
    $stmt->execute($data);
    
    // Check and send SMS alert
    if ($_POST['ph_level'] < 6.5 || $_POST['ph_level'] > 8.5) {
        sendSMSAlert("ALERT: Pond pH is " . $_POST['ph_level']);
    }
    
    $success = "Water quality recorded successfully!";
}
?>
<?php include '../includes/header.php'; ?>
<div class="form-container">
    <h2>Record Water Quality</h2>
    <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>
    
    <form method="POST">
        <select name="pond_id" required>
            <option value="">Select Pond</option>
            <?php
            $farmer_id = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT id, name FROM ponds WHERE farmer_id = ?");
            $stmt->execute([$farmer_id]);
            while ($pond = $stmt->fetch()) {
                echo "<option value='{$pond['id']}'>{$pond['name']}</option>";
            }
            ?>
        </select>
        
        <input type="number" step="0.1" name="water_temp" placeholder="Water Temp (°C)" required>
        <input type="number" step="0.1" name="ph_level" placeholder="pH Level" required>
        <input type="number" step="0.1" name="dissolved_oxygen" placeholder="DO (mg/L)" required>
        
        <button type="submit" class="btn-primary">Record</button>
    </form>
</div>
<?php include '../includes/footer.php'; ?>