<?php
// This file handles real-time monitoring for farmers
require_once '../../config/database.php';
require_once '../../includes/auth.php'; // Role check
checkRole('farmer'); // Only farmers access

// Add manual input form
// Display recent readings with auto-refresh
?>