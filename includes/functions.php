<?php
/**
 * Mugwe Fish Pond - Global Functions Library (PHP 7.4 Compatible)
 */

require_once 'auth.php';
require_once '../config/database.php';

/**
 * Format Ugandan Shillings
 */
function formatUGX($amount, $decimals = 0) {
    return 'UGX ' . number_format($amount, $decimals, '.', ',');
}

/**
 * Get water quality status
 */
function getWaterQualityStatus($ph, $temp, $do) {
    $status = 'good';
    $issues = [];
    
    if ($ph < 6.5 || $ph > 8.5) {
        $status = 'critical';
        $issues[] = "pH: " . round($ph, 1);
    } elseif ($ph < 6.8 || $ph > 8.2) {
        $status = 'warning';
        $issues[] = "pH: " . round($ph, 1);
    }
    
    if ($temp < 20 || $temp > 30) {
        $status = $status == 'critical' ? 'critical' : 'warning';
        $issues[] = "Temp: " . round($temp, 1) . "°C";
    }
    
    if ($do < 5) {
        $status = $status == 'critical' ? 'critical' : 'warning';
        $issues[] = "DO: " . round($do, 1) . " mg/L";
    }
    
    return [
        'status' => $status,
        'issues' => $issues,
        'color' => $status == 'critical' ? '#ef4444' : ($status == 'warning' ? '#f59e0b' : '#10b981')
    ];
}

/**
 * Calculate FCR (Feed Conversion Ratio)
 */
function calculateFCR($feed_used, $fish_weight_gain) {
    if ($fish_weight_gain == 0) return 0;
    return round($feed_used / $fish_weight_gain, 2);
}

/**
 * Get pond summary stats
 */
function getPondStats($pdo, $pond_id) {
    $stats = [
        'fish_count' => 0,
        'total_weight' => 0,
        'feed_used' => 0,
        'revenue' => 0
    ];
    
    // Fish stocks
    $stmt = $pdo->prepare("SELECT SUM(quantity) as count, SUM(quantity * avg_weight) as weight FROM fish_stocks WHERE pond_id = ?");
    $stmt->execute([$pond_id]);
    $fish = $stmt->fetch();
    $stats['fish_count'] = $fish['count'] ?? 0;
    $stats['total_weight'] = $fish['weight'] ?? 0;
    
    // Feed used (last 30 days)
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM feed_records WHERE pond_id = ? AND feed_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([$pond_id]);
    $stats['feed_used'] = $stmt->fetch()['total'] ?? 0;
    
    // Revenue
    $stmt = $pdo->prepare("SELECT SUM(total_revenue) as revenue FROM harvest_records WHERE pond_id = ?");
    $stmt->execute([$pond_id]);
    $stats['revenue'] = $stmt->fetch()['revenue'] ?? 0;
    
    return $stats;
}

/**
 * Send SMS notification
 */
function sendSMSAlert($message, $phone = null) {
    return DatabaseConfig::sendSMS($message, $phone);
}

/**
 * Validate farmer pond access
 */
function validatePondAccess($pdo, $farmer_id, $pond_id) {
    $stmt = $pdo->prepare("SELECT id FROM ponds WHERE id = ? AND farmer_id = ?");
    $stmt->execute([$pond_id, $farmer_id]);
    return $stmt->fetch() ? true : false;
}

/**
 * Get navigation menu based on role
 */
function getNavMenu($role) {
    $admin_menu = [
        ['icon' => '🏠', 'title' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => '🏞️', 'title' => 'Ponds', 'url' => 'ponds.php'],
        ['icon' => '🐟', 'title' => 'Fish Stocks', 'url' => 'fish_stocks.php'],
        ['icon' => '👥', 'title' => 'Users', 'url' => 'users.php'],
        ['icon' => '📊', 'title' => 'Reports', 'url' => 'reports.php']
    ];
    
    $farmer_menu = [
        ['icon' => '🏠', 'title' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => '🏞️', 'title' => 'My Ponds', 'url' => 'my_ponds.php'],
        ['icon' => '🥬', 'title' => 'Feed Records', 'url' => 'feed_records.php'],
        ['icon' => '🌾', 'title' => 'Harvest', 'url' => 'harvest.php']
    ];
    
    $vet_menu = [
        ['icon' => '🏠', 'title' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => '🏞️', 'title' => 'Ponds', 'url' => 'ponds.php'],
        ['icon' => '🐟', 'title' => 'Health Records', 'url' => 'health_records.php']
    ];
    
    switch($role) {
        case 'admin': return $admin_menu;
        case 'farmer': return $farmer_menu;
        case 'vet': return $vet_menu;
        default: return [['icon' => '🏠', 'title' => 'Login', 'url' => '../public/login.php']];
    }
}

/**
 * Anti-XSS sanitization
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random password
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Check if water quality is critical
 */
function isCriticalAlert($ph, $temp = null, $do = null) {
    return ($ph < 6.5 || $ph > 8.5) || 
           ($temp && ($temp < 18 || $temp > 32)) || 
           ($do && $do < 4);
}

/**
 * Get role color class - PHP 7.4 Compatible (FIXED!)
 */
function getRoleColor($role) {
    switch($role) {
        case 'admin':
            return 'bg-purple-100 text-purple-800';
        case 'farmer':
            return 'bg-green-100 text-green-800';
        case 'vet':
            return 'bg-orange-100 text-orange-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>