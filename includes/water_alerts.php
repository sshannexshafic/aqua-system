<?php
function checkWaterQualityAlerts($db, $pond_id, $data) {
    $alerts = [];
    
    if ($data['ph_level'] < 6.5 || $data['ph_level'] > 8.5) {
        $alerts[] = ["pH Critical: {$data['ph_level']} (Ideal 6.5-8.5)", 'critical'];
    }
    if ($data['dissolved_oxygen'] < 4) {
        $alerts[] = ["Low Dissolved Oxygen: {$data['dissolved_oxygen']} mg/L", 'critical'];
    }
    if (isset($data['turbidity']) && $data['turbidity'] > 40) {
        $alerts[] = ["High Turbidity: {$data['turbidity']}", 'high'];
    }
    if ($data['water_temp'] < 20 || $data['water_temp'] > 32) {
        $alerts[] = ["Temperature Alert: {$data['water_temp']}°C", 'high'];
    }

    foreach ($alerts as [$msg, $sev]) {
        $db->prepare("INSERT INTO alerts (pond_id, message, severity) VALUES (?, ?, ?)")
           ->execute([$pond_id, $msg, $sev]);
    }
    return count($alerts) > 0;
}
?>