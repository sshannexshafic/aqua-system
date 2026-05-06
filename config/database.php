<?php
/**
 *  Aquaculture SManagement ystem - Database Configuration
 * Optimized for Ugandan hosting providers (Hostinger, Truehost, etc.)
 */

class DatabaseConfig {
    
    // === PRODUCTION SETTINGS ===
    public static $DB_HOST = 'localhost';           // Usually 'localhost'
    public static $DB_NAME = 'aquaculture_system';  // Your database name
    public static $DB_USER = 'root';                // Database username
    public static $DB_PASS = '';                    // Database password
    
    // === SMS CONFIGURATION (Africa's Talking - Uganda) ===
    public static $SMS_USERNAME = 'sandbox';        // Your AT username
    public static $SMS_API_KEY = 'your_api_key_here'; // Get from africastalking.com
    public static $MANAGER_PHONE = '+256772123456'; // Mugwe Fish Pond Manager
    
    // === SYSTEM SETTINGS ===
    public static $SYSTEM_NAME = 'Aquaculture Management System AMS';
    public static $TIMEZONE = 'Africa/Nairobi';     // EAT (Uganda)
    public static $CURRENCY = 'UGX';
    
    // === SECURITY SETTINGS ===
    public static $ENCRYPTION_KEY = 'Aquaculture_management_system_2026'; // Change this!
    
    /**
     * Get PDO Database Connection
     */
    public static function getConnection() {
        $host = self::$DB_HOST;
        $dbname = self::$DB_NAME;
        $username = self::$DB_USER;
        $password = self::$DB_PASS;
        
        $pdo = null;
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, $username, $password, $options);
            
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . 
                "<br><small>Check config/database.php settings</small>");
        }
        
        return $pdo;
    }
    

    // Gmail SMTP Settings - UPDATE THESE!
public static $SMTP_USER = 'ssewanyanashafic266@gmail.com';        // Your Gmail
public static $SMTP_PASS = 'abcd efgh ijkl mnop';     // Gmail App Password
    /**
     * Test Database Connection
     */
    public static function testConnection() {
        try {
            $pdo = self::getConnection();
            return [
                'status' => 'success',
                'message' => 'Database connected successfully!'
            ];
        } catch(Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send SMS Alert (Africa's Talking)
     */
    public static function sendSMS($message, $phone = null) {
        $phone = $phone ?: self::$MANAGER_PHONE;
        
        $url = 'https://api.africastalking.com/version1/messaging';
        $data = [
            'username' => self::$SMS_USERNAME,
            'to' => $phone,
            'message' => "[Aqua Management System] $message"
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    "Content-Type: application/json",
                    "apiKey: " . self::$SMS_API_KEY
                ],
                'content' => json_encode($data)
            ]
        ]);
        
        $result = @file_get_contents($url, false, $context);
        return $result ? json_decode($result, true) : false;
    }
    
    /**
     * Log System Activity
     */
    public static function logActivity($user_id, $action, $details = '') {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
}

// Set timezone
date_default_timezone_set(DatabaseConfig::$TIMEZONE);

// Test connection on first load (development only)
if (isset($_GET['test_db'])) {
    header('Content-Type: application/json');
    echo json_encode(DatabaseConfig::testConnection());
    exit;
}

/**
 * Quick Setup Instructions:
 * 1. Update DB_USER, DB_PASS with your hosting credentials
 * 2. Create database: `aquaculture_system`
 * 3. Run: `import database/schema.sql`
 * 4. Test: `yoursite.com/config/database.php?test_db=1`
 * 5. Get Africa's Talking API: africastalking.com
 */
?>