<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
//require_once '../config/database.php';//


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mugwe Fish Pond - Aquaculture Management System</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="landing-page">
    <div class="hero">
        <div class="hero-content">
            <img src="../assets/images/logo.png" alt="Mugwe Fish Pond" class="logo-large">
            <h1>Aquaculture Management System</h1>
            <p>Digital solution for Mugwe Fish Pond, Busolwe Town Council, Butaleja District</p>
            
            <div class="cta-buttons">
                <a href="login.php" class="btn-primary btn-large">🚀 Get Started</a>
                <a href="#features" class="btn-secondary btn-large">📱 Features</a>
            </div>
        </div>
        
        <div class="hero-image">
            <div class="pond-animation">
                <div class="fish fish1">🐟</div>
                <div class="fish fish2">🐠</div>
                <div class="fish fish3">🐡</div>
            </div>
        </div>
    </div>

    <section id="features" class="features">
        <div class="container">
            <h2>Why Choose Our System?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Real-time Monitoring</h3>
                    <p>Track water quality, fish growth, and pond health instantly</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💧</div>
                    <h3>SMS Alerts</h3>
                    <p>Get instant notifications for critical water parameters</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile First</h3>
                    <p>Works perfectly on Android phones with MTN/Airtel data</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>Revenue Tracking</h3>
                    <p>Complete harvest and sales management with profit analytics</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="landing-footer">
        <div class="container">
            <p>&copy; 2026 Mugwe Fish Pond, Busolwe, Butaleja District, Uganda</p>
            <p>Built with Love for Ugandan fish farmers</p>
        </div>
    </footer>
</body>
</html>

<style>
.landing-page { font-family: 'Poppins', sans-serif; }
.hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: relative;
    overflow: hidden;
}

.hero-content {
    flex: 1;
    max-width: 600px;
    padding: 2rem;
    z-index: 2;
}

.logo-large { width: 120px; height: auto; margin-bottom: 2rem; }

.hero-content h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.hero-content p {
    font-size: 1.3rem;
    margin-bottom: 3rem;
    opacity: 0.95;
}

.cta-buttons {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.btn-large {
    padding: 1.25rem 2.5rem;
    font-size: 1.1rem;
    border-radius: 50px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.hero-image {
    flex: 1;
    position: relative;
    height: 100vh;
}

.pond-animation {
    position: absolute;
    top: 20%;
    right: 10%;
    font-size: 4rem;
    animation: swim 20s infinite linear;
}

.fish {
    position: absolute;
    animation: swim 15s infinite linear;
}

.fish1 { top: 10%; animation-delay: 0s; }
.fish2 { top: 40%; animation-delay: 5s; font-size: 3rem; }
.fish3 { top: 70%; animation-delay: 10s; }

@keyframes swim {
    0% { transform: translateX(100vw) rotate(0deg); }
    100% { transform: translateX(-100px) rotate(360deg); }
}

.features {
    padding: 6rem 2rem;
    background: #f8fafc;
}

.container { max-width: 1200px; margin: 0 auto; }

.features h2 {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 4rem;
    color: #1e2937;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
}

.feature-card {
    background: white;
    padding: 2.5rem;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-10px);
}

.feature-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #1e2937;
}

.landing-footer {
    background: #1e2937;
    color: white;
    text-align: center;
    padding: 3rem 2rem;
}

@media (max-width: 768px) {
    .hero { flex-direction: column; text-align: center; }
    .hero-content h1 { font-size: 2.5rem; }
    .cta-buttons { justify-content: center; }
    .pond-animation { display: none; }
}
</style>