<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$type = $data['type'] ?? '';
$record = $data['data'] ?? [];
$user_id = $_SESSION['user_id'];

$response = ['success' => false, 'message' => 'Unknown sync type'];

try {
    switch ($type) {
        case 'water_quality':
            // Save water quality reading
            if (isset($record['pond_id'], $record['ph'], $record['temperature'], $record['oxygen'])) {
                // Verify pond belongs to user
                $check_sql = "SELECT id FROM ponds WHERE id = ? AND user_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $record['pond_id'], $user_id);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows === 0) {
                    $response = ['success' => false, 'message' => 'Unauthorized pond access'];
                    break;
                }
                $check_stmt->close();
                
                $sql = "INSERT INTO water_quality (pond_id, ph, temperature, oxygen, recorded_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iddd", 
                    $record['pond_id'], 
                    $record['ph'], 
                    $record['temperature'], 
                    $record['oxygen']
                );
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Water quality saved', 'id' => $stmt->insert_id];
                } else {
                    throw new Exception('Database error: ' . $stmt->error);
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Missing required fields: pond_id, ph, temperature, oxygen'];
            }
            break;
            
        case 'health_record':
            // Save fish health record
            if (isset($record['fish_id'], $record['symptom'])) {
                $sql = "INSERT INTO health_records (fish_id, symptom, severity, notes, recorded_at, user_id) 
                        VALUES (?, ?, ?, ?, NOW(), ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssi", 
                    $record['fish_id'], 
                    $record['symptom'], 
                    $record['severity'] ?? 'mild', 
                    $record['notes'] ?? '', 
                    $user_id
                );
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Health record saved', 'id' => $stmt->insert_id];
                } else {
                    throw new Exception('Database error: ' . $stmt->error);
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Missing required fields: fish_id, symptom'];
            }
            break;
            
        case 'feeding_record':
            // Save feeding record
            if (isset($record['pond_id'], $record['quantity_kg'])) {
                // Verify pond belongs to user
                $check_sql = "SELECT id FROM ponds WHERE id = ? AND user_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $record['pond_id'], $user_id);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows === 0) {
                    $response = ['success' => false, 'message' => 'Unauthorized pond access'];
                    break;
                }
                $check_stmt->close();
                
                $sql = "INSERT INTO feeding_schedules (pond_id, feed_type, quantity_kg, time_of_day, created_at) 
                        VALUES (?, ?, ?, TIME(NOW()), NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isd", 
                    $record['pond_id'], 
                    $record['feed_type'] ?? 'standard', 
                    $record['quantity_kg']
                );
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Feeding record saved', 'id' => $stmt->insert_id];
                } else {
                    throw new Exception('Database error: ' . $stmt->error);
                }
                $stmt->close();
            } else {
                $response = ['success' => false, 'message' => 'Missing required fields: pond_id, quantity_kg'];
            }
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown sync type: ' . $type];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// Return JSON response
echo json_encode($response);
?>