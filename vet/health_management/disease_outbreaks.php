<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
checkRole('vet'); // Only vets access

// View all disease reports from farmers
// Provide treatment recommendations
// Update health status
?>