<?php
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo DatabaseConfig::$SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/style.css">
<script src="../assets/js/charts.js" defer></script>
    <link rel="icon" href="../assets/images/logo.png">
    <meta name="theme-color" content="#10b981">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <button class="mobile-toggle">☰</button>
    <?php include 'sidebar.php'; ?>
    <div class="content-wrapper">
    <?php endif; ?>

    <main class="<?php echo isLoggedIn() ? 'main-content' : ''; ?>">