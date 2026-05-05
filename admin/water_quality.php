<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

if ($_POST) {
    $pond_id = $_POST['pond_id'];

    $stmt = $db->prepare("INSERT INTO water_quality (water_temp, ph_level, dissolved_oxygen, pond_id) VALUES (?, ?, ?, ?)");
    
    // Execute insert
    if ($stmt->execute([
        $_POST['water_temp'],
        $_POST['ph_level'],
        $_POST['dissolved_oxygen'],
        $pond_id
    ])) {

        // Collect values
        $wq = [
            'ph' => $_POST['ph_level'],
            'temp' => $_POST['water_temp'],
            'do' => $_POST['dissolved_oxygen']
        ];

        // Smart alerts
        $alerts = [];

        if ($wq['ph'] < 6.5 || $wq['ph'] > 8.5) {
            $alerts[] = "pH Critical: {$wq['ph']}";
        }

        if ($wq['do'] < 4) {
            $alerts[] = "Low Dissolved Oxygen: {$wq['do']} mg/L";
        }

        if ($wq['temp'] < 20 || $wq['temp'] > 30) {
            $alerts[] = "Temperature out of range: {$wq['temp']}°C";
        }

        // If any alerts exist
        if (!empty($alerts)) {
            $message = "🚨 ALERT: " . implode(" | ", $alerts);

            // Send notifications (make sure these functions exist)
            sendSMSAlert($message);
            sendEmailAlert("Critical Water Quality", $message);

            $error = $message; // show alert in UI
        } else {
            $success = "Water quality recorded successfully!";
        }
    }
}