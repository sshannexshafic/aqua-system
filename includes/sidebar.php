<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    return; // Don't show sidebar if not logged in
}

$role = $_SESSION['role'];
$menu_items = getNavMenu($role);
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="user-profile">
            <div class="avatar">
                <i class="<?php echo $role == 'admin' ? 'icon-admin' : ($role == 'farmer' ? 'icon-farmer' : 'icon-vet'); ?>"></i>
            </div>
            <div>
                <div class="username"><?php echo $_SESSION['username']; ?></div>
                <div class="role <?php echo $role; ?>">
                    <?php echo ucfirst($role); ?> Panel
                </div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="menu-list">
            <?php foreach($menu_items as $item): ?>
            <li class="<?php echo $current_page == $item['url'] ? 'active' : ''; ?>">
                <a href="<?php echo ($role == 'farmer' || $role == 'vet') ? '../farmer/' . $item['url'] : '../admin/' . $item['url']; ?>">
                    <span class="menu-icon"><?php echo $item['icon']; ?></span>
                    <span class="menu-text"><?php echo $item['title']; ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="quick-actions">
            <a href="../public/logout.php" class="btn-logout">
                <i class="icon-logout"></i> Logout
            </a>
        </div>
        <div class="system-info">
        
        
        </div>
    </div>
</aside>

<style>
/* Sidebar Styles */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    color: white;
    z-index: 1000;
    box-shadow: 4px 0 20px rgba(0,0,0,0.3);
    overflow-y: auto;
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.username {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}

.role {
    font-size: 0.85rem;
    opacity: 0.8;
    text-transform: uppercase;
    font-weight: 500;
}

.role.admin { color: #a78bfa; }
.role.farmer { color: #10b981; }
.role.vet { color: #f59e0b; }

.sidebar-nav {
    padding: 1.5rem 0;
}

.menu-list {
    list-style: none;
    padding: 0;
}

.menu-list li {
    margin-bottom: 0.25rem;
}

.menu-list a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.menu-list a:hover,
.menu-list li.active a {
    background: rgba(255,255,255,0.1);
    color: white;
    border-left-color: #3b82f6;
}

.menu-icon {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.menu-text {
    font-weight: 500;
}

.sidebar-footer {
    padding: 2rem 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    margin-top: auto;
}

.quick-actions {
    margin-bottom: 1.5rem;
}

.btn-logout {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #f87171;
    text-decoration: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-logout:hover {
    background: rgba(248, 113, 113, 0.1);
    color: #ef4444;
}

.system-info {
    text-align: center;
    opacity: 0.6;
    font-size: 0.8rem;
    line-height: 1.4;
}

/* Mobile Sidebar Toggle */
.mobile-toggle {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: rgba(30, 41, 59, 0.95);
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 12px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .mobile-toggle {
        display: block;
    }
    
    .content-wrapper {
        margin-left: 0;
        padding-top: 70px;
    }
}

/* Icons (using Unicode) */
.icon-admin::before { content: "👨‍💼"; }
.icon-farmer::before { content: "👨‍🌾"; }
.icon-vet::before { content: "🐱‍💻"; }
.icon-logout::before { content: "🚪"; }
.icon-plus::before { content: "➕"; }
.icon-list::before { content: "📋"; }
.icon-save::before { content: "💾"; }
</style>

<script>
// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.querySelector('.mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggle) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar on outside click
    document.addEventListener('click', function(e) {
        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    });
});
</script>

