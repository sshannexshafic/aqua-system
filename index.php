<?php
// index.php - Beautiful Landing Page
require_once 'includes/db_connection.php';
require_once '../includes/auth.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mugwe Fish Pond - Aquaculture Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero {
            color: white;
            text-align: center;
            padding: 100px 20px;
        }
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 3rem;
            opacity: 0.9;
        }
        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-large {
            padding: 16px 32px;
            font-size: 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-primary {
            background: white;
            color: #3b82f6;
        }
        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
    </style>
</head>
<body class="landing-page">
    <div class="hero">
        <img src="assets/images/logo.png" alt="Mugwe Fish Pond" style="width:120px; margin-bottom:20px;">
        <h1>Mugwe Fish Pond</h1>
        <p>Smart Aquaculture Management System</p>
        <p style="font-size:1.1rem; max-width:600px; margin:0 auto 3rem;">
            Managing fish ponds has never been easier. Track water quality, fish health, feeding, and harvests in one place.
        </p>
        
        <div class="cta-buttons">
            <a href="public/login.php" class="btn-large btn-primary">Login to Dashboard</a>
            <a href="public/register.php" class="btn-large btn-secondary">Create New Account</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>