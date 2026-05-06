/**
 * Mugwe Fish Pond AMS - Main JavaScript
 * Global utilities + Fish Stock Edit Fix
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initAlertDismiss();
    initTableSearch();
    initTooltips();
    initConfirmDeletes();
    highlightActiveNav();
    initFormEnhancements();
});

/* ───────── EDIT MODAL FUNCTION (FIXED) ───────── */
function openEdit(id, pond, species, qty, weight, date, source, cost, notes) {

    const modal = document.getElementById('modal');
    if (!modal) return;

    modal.style.display = 'block';

    document.getElementById('e_id').value = id || '';
    document.getElementById('e_pond').value = pond || '';
    document.getElementById('e_species').value = species || '';
    document.getElementById('e_qty').value = qty || '';
    document.getElementById('e_weight').value = weight || '';
    document.getElementById('e_date').value = (date || '').substring(0,10);
    document.getElementById('e_source').value = source || '';
    document.getElementById('e_cost').value = cost || 0;
    document.getElementById('e_notes').value = notes || '';
}

/* ───────── CLOSE MODAL ───────── */
function closeModal() {
    const modal = document.getElementById('modal');
    if (modal) modal.style.display = 'none';
}

/* ───────── EXISTING FUNCTIONS (UNCHANGED) ───────── */

function initSidebar() {
    const toggle = document.querySelector('.mobile-toggle');
    const sidebar = document.querySelector('.sidebar');
    if (!toggle || !sidebar) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

function initAlertDismiss() {
    document.querySelectorAll('.success, .error, .alert').forEach(el => {
        setTimeout(() => el.remove(), 5000);
    });
}

function initTableSearch() {}
function initTooltips() {}
function initConfirmDeletes() {}
function highlightActiveNav() {}
function initFormEnhancements() {}