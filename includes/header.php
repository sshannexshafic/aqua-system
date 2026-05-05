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

    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aquaculture Management System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="dashboard.css" href="/assets/css/dashboard.css">
    
    <!-- PWA Support - Add this section -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4CAF50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Service Worker Registration - ADD THIS CODE HERE -->
    <script>
        // Register Service Worker for Offline Support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/public/sw.js')
                    .then(function(registration) {
                        console.log('Service Worker registered successfully:', registration.scope);
                        
                        // Check for updates
                        registration.addEventListener('updatefound', function() {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', function() {
                                if (newWorker.state === 'activated') {
                                    console.log('New Service Worker activated');
                                    // Notify user to refresh
                                    if (confirm('New version available! Refresh to update?')) {
                                        window.location.reload();
                                    }
                                }
                            });
                        });
                    })
                    .catch(function(error) {
                        console.log('Service Worker registration failed:', error);
                    });
            });
        }
        
        // Optional: Track online/offline status
        window.addEventListener('online', function() {
            console.log('You are now back online');
            // Show notification
            if (typeof showNotification === 'function') {
                showNotification('Connected to internet', 'success');
            }
        });
        
        window.addEventListener('offline', function() {
            console.log('You are now offline - working in offline mode');
            // Show notification
            if (typeof showNotification === 'function') {
                showNotification('You are offline. Some features limited.', 'warning');
            }
        });
    </script>
    
</head>
<body>
</head>
<body>
    <body>
    <!-- Offline Status Indicator -->
    <div id="offlineIndicator" class="offline-indicator">
        📡 You are offline - Working in offline mode
    </div>
    
    <!-- Rest of your content -->
    <?php if (isLoggedIn()): ?>
    <button class="mobile-toggle">☰</button>
    <?php include 'sidebar.php'; ?>
    <div class="content-wrapper">
    <?php endif; ?>

    <main class="<?php echo isLoggedIn() ? 'main-content' : ''; ?>">

