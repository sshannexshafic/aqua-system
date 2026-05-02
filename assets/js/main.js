cat > /home/claude/aquaculturesystem_final/assets/js/main.js << 'EOF'
/**
 * Mugwe Fish Pond AMS - Main JavaScript
 * Global utilities, UI interactions, and mobile support
 */

'use strict';

// ─── DOM Ready ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initAlertDismiss();
    initTableSearch();
    initTooltips();
    initConfirmDeletes();
    highlightActiveNav();
    initFormEnhancements();
});

// ─── Sidebar (Mobile Toggle) ──────────────────────────────────
function initSidebar() {
    const toggle = document.querySelector('.mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!toggle || !sidebar) return;

    // Create overlay if missing
    if (!overlay) {
        const div = document.createElement('div');
        div.className = 'sidebar-overlay';
        document.body.appendChild(div);
    }

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        document.querySelector('.sidebar-overlay').classList.toggle('active');
    });

    document.addEventListener('click', function (e) {
        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
            sidebar.classList.remove('active');
            const ov = document.querySelector('.sidebar-overlay');
            if (ov) ov.classList.remove('active');
        }
    });
}

// ─── Auto-dismiss alerts after 5 seconds ─────────────────────
function initAlertDismiss() {
    document.querySelectorAll('.success, .error, .alert').forEach(function (el) {
        // Add close button
        const btn = document.createElement('button');
        btn.innerHTML = '&times;';
        btn.className = 'alert-close';
        btn.style.cssText = 'float:right;background:none;border:none;font-size:1.2rem;cursor:pointer;opacity:0.6;';
        btn.addEventListener('click', () => el.remove());
        el.prepend(btn);

        // Auto-dismiss
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 5000);
    });
}

// ─── Live table search ────────────────────────────────────────
function initTableSearch() {
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(function (input) {
        const targetId = input.getAttribute('data-table-search');
        const table = document.getElementById(targetId);
        if (!table) return;

        input.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
}

// ─── Tooltips ─────────────────────────────────────────────────
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(function (el) {
        el.setAttribute('title', el.getAttribute('data-tooltip'));
    });
}

// ─── Confirm delete actions ───────────────────────────────────
function initConfirmDeletes() {
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'Are you sure you want to delete this?';
            if (!confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
}

// ─── Highlight active sidebar nav ────────────────────────────
function highlightActiveNav() {
    const current = window.location.pathname.split('/').pop();
    document.querySelectorAll('.menu-list a').forEach(function (link) {
        if (link.getAttribute('href') && link.getAttribute('href').includes(current)) {
            link.closest('li') && link.closest('li').classList.add('active');
        }
    });
}

// ─── Form enhancements ────────────────────────────────────────
function initFormEnhancements() {
    // Prevent double-submit
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                const original = btn.textContent;
                btn.textContent = 'Saving...';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = original;
                }, 8000);
            }
        });
    });

    // Auto-format UGX inputs
    document.querySelectorAll('input[data-currency]').forEach(function (input) {
        input.addEventListener('blur', function () {
            const val = parseFloat(this.value.replace(/,/g, ''));
            if (!isNaN(val)) {
                this.value = val.toLocaleString('en-UG');
            }
        });
    });
}

// ─── Utility: Format UGX in page ─────────────────────────────
function formatUGX(amount) {
    return 'UGX ' + parseFloat(amount).toLocaleString('en-UG', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

// ─── Utility: Show toast notification ────────────────────────
function showToast(message, type) {
    type = type || 'info';
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = message;
    toast.style.cssText = [
        'position:fixed', 'bottom:2rem', 'right:2rem',
        'padding:1rem 1.5rem', 'border-radius:12px',
        'font-weight:600', 'z-index:9999',
        'animation:slideInRight 0.3s ease',
        type === 'success' ? 'background:#10b981;color:white;' :
        type === 'error'   ? 'background:#ef4444;color:white;' :
                             'background:#3b82f6;color:white;'
    ].join(';');

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.4s ease';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// ─── Sidebar Overlay CSS (injected once) ─────────────────────
(function injectOverlayCSS() {
    const style = document.createElement('style');
    style.textContent = [
        '.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;}',
        '.sidebar-overlay.active{display:block;}',
        '@keyframes slideInRight{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}'
    ].join('');
    document.head.appendChild(style);
}());
EOF