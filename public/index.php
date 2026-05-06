<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') header("Location: ../admin/dashboard.php");
    elseif ($role === 'vet') header("Location: ../vet/dashboard.php");
    else header("Location: ../farmer/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aquaculture Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --teal: #0d9488; --teal-l: #14b8a6; --teal-d: #0f766e;
            --water: #06b6d4; --dark: #0a1628; --dark2: #112240;
            --text: #e2f0ff; --muted: #7fa8c9; --white: #ffffff; --gold: #f59e0b;
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'DM Sans', sans-serif; background: var(--dark); color: var(--text); overflow-x: hidden; }

        /* NAV */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            padding: 1rem 4rem;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(10,22,40,0.85);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid rgba(13,148,136,0.2);
        }
        .nav-brand {
            font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem;
            color: var(--teal-l); letter-spacing: -0.03em;
            display: flex; align-items: center; gap: .75rem;
        }
        .nav-brand img {
            height: 42px; width: auto;
            object-fit: contain;
            filter: drop-shadow(0 0 8px rgba(13,148,136,.6));
        }
        .nav-brand span { color: var(--white); }
        .nav-cta {
            background: var(--teal); color: white; font-weight: 500;
            padding: .65rem 1.6rem; border-radius: 50px; text-decoration: none;
            font-size: .9rem; transition: background .2s, transform .2s;
            border: 1px solid var(--teal-l);
        }
        .nav-cta:hover { background: var(--teal-l); transform: translateY(-1px); }

        /* HERO */
        .hero {
            min-height: 100vh;
            display: grid; grid-template-columns: 1fr 1fr;
            align-items: center; padding: 8rem 4rem 4rem;
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 70% 50%, rgba(13,148,136,.18) 0%, transparent 70%),
                radial-gradient(ellipse 50% 40% at 20% 80%, rgba(6,182,212,.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 10%, rgba(15,118,110,.15) 0%, transparent 65%);
            animation: bgPulse 8s ease-in-out infinite alternate;
        }
        .hero::after {
            content: ''; position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(13,148,136,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(13,148,136,.06) 1px, transparent 1px);
            background-size: 60px 60px; pointer-events: none;
        }
        @keyframes bgPulse { from{opacity:.7} to{opacity:1} }

        .hero-left { position: relative; z-index: 2; }

        /* LOGO in hero */
        .hero-logo {
            height: 90px; width: auto;
            object-fit: contain;
            margin-bottom: 1.75rem;
            display: block;
            filter: drop-shadow(0 0 24px rgba(13,148,136,.55));
            animation: fadeUp .7s ease both;
        }

        .badge {
            display: inline-flex; align-items: center; gap: .5rem;
            background: rgba(13,148,136,.15); border: 1px solid rgba(13,148,136,.4);
            color: var(--teal-l); font-size: .8rem; font-weight: 500;
            padding: .4rem 1rem; border-radius: 50px; margin-bottom: 1.75rem;
            animation: fadeUp .8s .1s ease both;
        }
        .badge::before { content: '●'; font-size: .5rem; animation: blink 2s infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: clamp(2.8rem, 5vw, 4.5rem); font-weight: 800;
            line-height: 1.05; letter-spacing: -0.04em; margin-bottom: 1.5rem;
            animation: fadeUp .9s .15s ease both;
        }
        h1 em {
            font-style: normal;
            background: linear-gradient(135deg, var(--teal-l), var(--water));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero-desc {
            font-size: 1.05rem; color: var(--muted); line-height: 1.75;
            max-width: 480px; margin-bottom: 2.5rem;
            animation: fadeUp 1s .25s ease both;
        }
        .cta-row { display: flex; gap: 1rem; flex-wrap: wrap; animation: fadeUp 1s .35s ease both; }
        .btn-primary-hero {
            background: linear-gradient(135deg, var(--teal), var(--teal-d));
            color: white; font-weight: 600; padding: 1rem 2.2rem; border-radius: 50px;
            text-decoration: none; font-size: 1rem;
            box-shadow: 0 0 30px rgba(13,148,136,.4); transition: all .3s;
            border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; gap: .5rem;
        }
        .btn-primary-hero:hover { transform: translateY(-3px); box-shadow: 0 0 50px rgba(13,148,136,.6); }
        .btn-outline-hero {
            background: transparent; color: var(--text); font-weight: 500;
            padding: 1rem 2.2rem; border-radius: 50px; text-decoration: none; font-size: 1rem;
            border: 1px solid rgba(255,255,255,.2); transition: all .3s;
            display: flex; align-items: center; gap: .5rem;
        }
        .btn-outline-hero:hover { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.4); transform: translateY(-2px); }

        .stats-row { display: flex; gap: 2.5rem; margin-top: 3rem; animation: fadeUp 1s .45s ease both; }
        .stat { border-left: 2px solid var(--teal); padding-left: 1rem; }
        .stat-num { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; color: var(--teal-l); }
        .stat-label { font-size: .78rem; color: var(--muted); margin-top: .1rem; }

        /* POND ANIMATION */
        .hero-right { position: relative; z-index: 2; display: flex; align-items: center; justify-content: center; }
        .pond-wrap { position: relative; width: 420px; height: 420px; }
        .pond-ring { position: absolute; border-radius: 50%; border: 1px solid rgba(13,148,136,.25); animation: ripple 4s ease-in-out infinite; }
        .pond-ring:nth-child(1) { inset: 0; animation-delay: 0s; }
        .pond-ring:nth-child(2) { inset: 30px; animation-delay: .8s; }
        .pond-ring:nth-child(3) { inset: 60px; animation-delay: 1.6s; }
        @keyframes ripple { 0%,100%{transform:scale(1);opacity:.4} 50%{transform:scale(1.04);opacity:1} }
        .pond-core {
            position: absolute; inset: 80px; border-radius: 50%;
            background: radial-gradient(circle at 40% 40%, #0d9488, #0a3d40);
            box-shadow: 0 0 60px rgba(13,148,136,.5), inset 0 0 40px rgba(0,0,0,.4);
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .wave { position: absolute; bottom: 0; left: -50%; width: 200%; height: 60%; background: rgba(6,182,212,.15); border-radius: 45%; animation: wave 6s linear infinite; }
        .wave:nth-child(2) { animation-duration: 9s; animation-direction: reverse; opacity: .5; }
        @keyframes wave { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

        /* LOGO inside pond core */
        .pond-logo {
            width: 70px; height: 70px; object-fit: contain;
            position: relative; z-index: 2;
            animation: float 3s ease-in-out infinite;
            filter: drop-shadow(0 0 10px rgba(255,255,255,.3)) brightness(1.2);
        }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

        .fish-orbit { position: absolute; inset: 0; animation: orbit 12s linear infinite; }
        .fish-orbit .fish-dot { position: absolute; top: 10px; left: 50%; transform: translateX(-50%); font-size: 1.6rem; }
        .fish-orbit:nth-child(5) { animation-duration: 18s; animation-direction: reverse; }
        .fish-orbit:nth-child(5) .fish-dot { top: auto; bottom: 10px; font-size: 1.2rem; }
        @keyframes orbit { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

        .data-pills { position: absolute; }
        .pill { background: rgba(10,22,40,.9); border: 1px solid rgba(13,148,136,.35); border-radius: 12px; padding: .6rem 1rem; font-size: .78rem; backdrop-filter: blur(10px); white-space: nowrap; display: flex; align-items: center; gap: .4rem; }
        .pill .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--teal-l); }
        .pill-1 { top: 50px; left: -60px; animation: floatPill 4s ease-in-out infinite; }
        .pill-2 { top: 180px; right: -70px; animation: floatPill 4s 1.5s ease-in-out infinite; }
        .pill-3 { bottom: 60px; left: -40px; animation: floatPill 4s 3s ease-in-out infinite; }
        @keyframes floatPill { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
        @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }

        /* FEATURES */
        .features { padding: 7rem 4rem; position: relative; }
        .features::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, var(--teal), transparent); }
        .section-label { font-size: .8rem; font-weight: 600; color: var(--teal-l); letter-spacing: .15em; text-transform: uppercase; margin-bottom: 1rem; }
        .section-title { font-family: 'Syne', sans-serif; font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 700; letter-spacing: -0.03em; margin-bottom: 4rem; max-width: 500px; }
        .section-title em { font-style: normal; color: var(--teal-l); }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; }
        .feat-card { background: var(--dark2); border: 1px solid rgba(13,148,136,.15); border-radius: 20px; padding: 2rem; transition: all .35s; position: relative; overflow: hidden; }
        .feat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, var(--teal), var(--water)); opacity: 0; transition: opacity .3s; }
        .feat-card:hover { transform: translateY(-6px); border-color: rgba(13,148,136,.4); box-shadow: 0 20px 40px rgba(0,0,0,.3); }
        .feat-card:hover::before { opacity: 1; }
        .feat-icon { width: 52px; height: 52px; background: rgba(13,148,136,.12); border: 1px solid rgba(13,148,136,.25); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem; }
        .feat-card h3 { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: .6rem; color: var(--white); }
        .feat-card p { font-size: .9rem; color: var(--muted); line-height: 1.65; }

        /* ROLES */
        .roles { padding: 5rem 4rem; background: var(--dark2); position: relative; }
        .roles::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(13,148,136,.4), transparent); }
        .roles-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 3rem; }
        .role-card { border-radius: 20px; padding: 2rem; text-align: center; border: 1px solid rgba(255,255,255,.06); transition: transform .3s; }
        .role-card:hover { transform: translateY(-4px); }
        .role-card.admin-card { background: linear-gradient(135deg, rgba(99,102,241,.15), rgba(99,102,241,.05)); border-color: rgba(99,102,241,.3); }
        .role-card.farmer-card { background: linear-gradient(135deg, rgba(13,148,136,.15), rgba(13,148,136,.05)); border-color: rgba(13,148,136,.3); }
        .role-card.vet-card { background: linear-gradient(135deg, rgba(245,158,11,.15), rgba(245,158,11,.05)); border-color: rgba(245,158,11,.3); }
        .role-emoji { font-size: 3rem; margin-bottom: 1rem; display: block; }
        .role-card h3 { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.15rem; margin-bottom: .5rem; }
        .role-card p { font-size: .85rem; color: var(--muted); line-height: 1.6; }

        /* FOOTER */
        footer { background: #060f1e; padding: 3rem 4rem; display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(13,148,136,.15); flex-wrap: wrap; gap: 1rem; }
        .footer-brand { font-family: 'Syne', sans-serif; font-weight: 700; color: var(--teal-l); display: flex; align-items: center; gap: .6rem; }
        .footer-brand img { height: 32px; width: auto; object-fit: contain; filter: drop-shadow(0 0 6px rgba(13,148,136,.5)); }
        footer p { font-size: .85rem; color: var(--muted); }
        .footer-link { color: var(--teal-l); text-decoration: none; font-size: .85rem; border: 1px solid rgba(13,148,136,.3); padding: .5rem 1.2rem; border-radius: 50px; transition: all .2s; }
        .footer-link:hover { background: rgba(13,148,136,.1); }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            nav { padding: 1rem 1.5rem; }
            .hero { grid-template-columns: 1fr; padding: 7rem 1.5rem 3rem; text-align: center; }
            .hero-logo { margin: 0 auto 1.5rem; }
            .hero-desc { margin: 0 auto 2rem; }
            .cta-row { justify-content: center; }
            .stats-row { justify-content: center; }
            .hero-right { display: none; }
            .features, .roles { padding: 4rem 1.5rem; }
            .roles-grid { grid-template-columns: 1fr; }
            footer { flex-direction: column; text-align: center; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <div class="nav-brand">
        <img src="../assets/images/logo1.png.png" alt="Logo">
        <span>Aquaculture</span> Management System
    </div>
    <a href="login.php" class="nav-cta">Sign In →</a>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-left">

        <!-- LOGO — prominent in hero -->

        <div class="badge">🌊 ACMS</div>

        <h1>Smart Fish Farming<br>for <em>Ugandan</em><br>Aquaculture</h1>

        <p class="hero-desc">
            Monitor water quality, track fish growth, manage feeding schedules,
            and analyse production data — all in one integrated platform built
            for Butaleja District farmers.
        </p>

        <div class="cta-row">
            <a href="login.php" class="btn-primary-hero">🚀 Get Started</a>
            <a href="#features" class="btn-outline-hero">📱 See Features</a>
        </div>

        <div class="stats-row">
            <div class="stat"><div class="stat-num">3</div><div class="stat-label">User Roles</div></div>
            <div class="stat"><div class="stat-num">24/7</div><div class="stat-label">Monitoring</div></div>
            <div class="stat"><div class="stat-num">100%</div><div class="stat-label">Local Built</div></div>
        </div>
    </div>

    <div class="hero-right">
        <div class="pond-wrap">
            <div class="pond-ring"></div>
            <div class="pond-ring"></div>
            <div class="pond-ring"></div>
            <div class="fish-orbit"><div class="fish-dot">🐟</div></div>
            <div class="fish-orbit"><div class="fish-dot">🐠</div></div>
            <div class="pond-core">
                <div class="wave"></div>
                <div class="wave"></div>
                <!-- LOGO inside the pond animation -->
                <img src="../assets/images/logo.png.png" alt="Logo" class="pond-logo">
            </div>
            <div class="data-pills">
                <div class="pill pill-1"><div class="dot"></div> pH: 7.2 · Normal</div>
                <div class="pill pill-2"><div class="dot"></div> Temp: 26°C</div>
                <div class="pill pill-3"><div class="dot"></div> DO: 6.8 mg/L</div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section id="features" class="features">
    <p class="section-label">Platform Features</p>
    <h2 class="section-title">Everything you need to<br><em>run a modern fish farm</em></h2>
    <div class="features-grid">
        <div class="feat-card"><div class="feat-icon">📊</div><h3>Real-time Monitoring</h3><p>Track water quality parameters — pH, temperature, dissolved oxygen — with instant alert flags for out-of-range readings.</p></div>
        <div class="feat-card"><div class="feat-icon">🌾</div><h3>Harvest Management</h3><p>Log harvest records, track weight, set sale prices, and generate revenue summaries automatically.</p></div>
        <div class="feat-card"><div class="feat-icon">🥬</div><h3>Feed Tracking</h3><p>Record daily feeding per pond, monitor feed costs, and spot consumption trends across your entire farm.</p></div>
        <div class="feat-card"><div class="feat-icon">🏥</div><h3>Veterinary Module</h3><p>Vets can log health records, track mortality, write recommendations and monitor pond conditions remotely.</p></div>
        <div class="feat-card"><div class="feat-icon">💰</div><h3>Financial Reports</h3><p>Full financial summaries — revenue, feed cost, profit margin — exportable and printable for each season.</p></div>
        <div class="feat-card"><div class="feat-icon">📱</div><h3>Mobile Friendly</h3><p>Works on any device with a browser. Optimised for Android phones running MTN or Airtel Uganda data.</p></div>
    </div>
</section>

<!-- ROLES -->
<section class="roles">
    <p class="section-label">Who Uses This System</p>
    <h2 class="section-title">Built for <em>three roles</em>,<br>working as one team</h2>
    <div class="roles-grid">
        <div class="role-card admin-card"><span class="role-emoji">👨‍💼</span><h3>Admin</h3><p>Oversees all ponds, manages user accounts, generates system-wide reports, and controls the full platform.</p></div>
        <div class="role-card farmer-card"><span class="role-emoji">👨‍🌾</span><h3>Farmer</h3><p>Records daily water quality, feed logs, fish stock details, and harvest data for their assigned ponds.</p></div>
        <div class="role-card vet-card"><span class="role-emoji">🏥</span><h3>Veterinarian</h3><p>Diagnoses pond health issues, records mortality, writes treatment recommendations, and tracks follow-ups.</p></div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div>
        <div class="footer-brand">
            <img src="../assets/images/logo1.png.png" alt="Logo">
            Aquaculture Management System
        </div>
        <p style="margin-top:.3rem;">AquacultureManagementSystem · © 2026</p>
    </div>
    <p>Built with ❤️ for Ugandan fish farmers</p>
    <a href="login.php" class="footer-link">Sign In →</a>
</footer>

</body>
</html>