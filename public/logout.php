<?php
require_once '../includes/auth.php';

// Log logout activity
require_once '../config/database.php';
DatabaseConfig::logActivity($_SESSION['user_id'] ?? 0, 'LOGOUT', $_SERVER['REMOTE_ADDR'] ?? '');

logout();
?>


