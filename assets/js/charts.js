cat > /home/claude/aquaculturesystem_final/assets/js/charts.js << 'EOF'
/**
 * Mugwe Fish Pond AMS - Charts & Data Visualisation
 * Lightweight canvas charts — no external dependencies
 */

'use strict';

// ─── Mini Bar Chart ───────────────────────────────────────────
function drawBarChart(canvasId, labels, data, options) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    options = options || {};

    const W = canvas.width  = canvas.offsetWidth  || 600;
    const H = canvas.height = canvas.offsetHeight || 300;
    const pad   = { top: 30, right: 20, bottom: 60, left: 70 };
    const chartW = W - pad.left - pad.right;
    const chartH = H - pad.top  - pad.bottom;

    const max = Math.max.apply(null, data) * 1.15 || 1;
    const color = options.color || '#3b82f6';

    ctx.clearRect(0, 0, W, H);

    // Gridlines
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 5; i++) {
        const y = pad.top + chartH - (chartH / 5) * i;
        ctx.beginPath();
        ctx.moveTo(pad.left, y);
        ctx.lineTo(pad.left + chartW, y);
        ctx.stroke();

        ctx.fillStyle = '#6b7280';
        ctx.font = '11px Inter, sans-serif';
        ctx.textAlign = 'right';
        const val = Math.round((max / 5) * i);
        ctx.fillText(
            options.prefix
                ? options.prefix + val.toLocaleString()
                : val.toLocaleString(),
            pad.left - 8,
            y + 4
        );
    }

    // Bars
    const barW = (chartW / labels.length) * 0.6;
    const gap  = chartW / labels.length;
    labels.forEach(function (label, i) {
        const barH  = (data[i] / max) * chartH;
        const x = pad.left + gap * i + (gap - barW) / 2;
        const y = pad.top + chartH - barH;

        // Bar fill
        const grad = ctx.createLinearGradient(x, y, x, y + barH);
        grad.addColorStop(0, color);
        grad.addColorStop(1, color + '80');
        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.roundRect(x, y, barW, barH, 4);
        ctx.fill();

        // Label
        ctx.fillStyle = '#374151';
        ctx.font = '11px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(label, x + barW / 2, H - pad.bottom + 18);

        // Value on top
        ctx.fillStyle = color;
        ctx.font = 'bold 11px Inter, sans-serif';
        ctx.fillText(
            options.prefix ? options.prefix + data[i].toLocaleString() : data[i].toLocaleString(),
            x + barW / 2,
            y - 6
        );
    });
}

// ─── Mini Line Chart ──────────────────────────────────────────
function drawLineChart(canvasId, labels, datasets, options) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    options = options || {};

    const W = canvas.width  = canvas.offsetWidth  || 600;
    const H = canvas.height = canvas.offsetHeight || 250;
    const pad = { top: 30, right: 20, bottom: 50, left: 60 };
    const chartW = W - pad.left - pad.right;
    const chartH = H - pad.top  - pad.bottom;

    const allVals = datasets.reduce(function (acc, ds) { return acc.concat(ds.data); }, []);
    const max = Math.max.apply(null, allVals) * 1.15 || 1;

    ctx.clearRect(0, 0, W, H);

    // Gridlines
    ctx.strokeStyle = '#f3f4f6';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
        const y = pad.top + chartH - (chartH / 4) * i;
        ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(pad.left + chartW, y); ctx.stroke();
        ctx.fillStyle = '#9ca3af'; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'right';
        ctx.fillText(Math.round((max / 4) * i), pad.left - 6, y + 4);
    }

    // Datasets
    datasets.forEach(function (ds) {
        const pts = ds.data.map(function (v, i) {
            return {
                x: pad.left + (chartW / (labels.length - 1)) * i,
                y: pad.top + chartH - (v / max) * chartH
            };
        });

        // Line
        ctx.beginPath();
        ctx.strokeStyle = ds.color || '#3b82f6';
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        pts.forEach(function (p, i) { i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y); });
        ctx.stroke();

        // Area fill
        ctx.lineTo(pts[pts.length - 1].x, pad.top + chartH);
        ctx.lineTo(pts[0].x, pad.top + chartH);
        ctx.closePath();
        const grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + chartH);
        grad.addColorStop(0, (ds.color || '#3b82f6') + '30');
        grad.addColorStop(1, (ds.color || '#3b82f6') + '00');
        ctx.fillStyle = grad;
        ctx.fill();

        // Dots
        pts.forEach(function (p) {
            ctx.beginPath();
            ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
            ctx.fillStyle = ds.color || '#3b82f6';
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
    });

    // X-axis labels
    labels.forEach(function (label, i) {
        const x = pad.left + (chartW / (labels.length - 1)) * i;
        ctx.fillStyle = '#6b7280'; ctx.font = '11px Inter,sans-serif'; ctx.textAlign = 'center';
        ctx.fillText(label, x, H - pad.bottom + 18);
    });
}

// ─── Donut Chart ──────────────────────────────────────────────
function drawDonutChart(canvasId, labels, data, colors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    const W = canvas.width  = canvas.offsetWidth  || 250;
    const H = canvas.height = canvas.offsetHeight || 250;
    const cx = W / 2, cy = H / 2;
    const radius = Math.min(W, H) / 2 - 20;
    const inner  = radius * 0.55;
    const total  = data.reduce(function (a, b) { return a + b; }, 0);
    const defColors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4'];

    ctx.clearRect(0, 0, W, H);

    let start = -Math.PI / 2;
    data.forEach(function (val, i) {
        const slice = (val / total) * 2 * Math.PI;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, radius, start, start + slice);
        ctx.closePath();
        ctx.fillStyle = (colors && colors[i]) || defColors[i % defColors.length];
        ctx.fill();
        start += slice;
    });

    // Inner circle (donut hole)
    ctx.beginPath();
    ctx.arc(cx, cy, inner, 0, 2 * Math.PI);
    ctx.fillStyle = '#fff';
    ctx.fill();

    // Centre label
    ctx.fillStyle = '#1f2937';
    ctx.font = 'bold 1.2rem Inter,sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(total.toLocaleString(), cx, cy);
}

// ─── Water Quality Gauge ──────────────────────────────────────
function drawGauge(canvasId, value, min, max, label, thresholds) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    thresholds = thresholds || { good: [min, max * 0.6], warn: [max * 0.6, max * 0.85] };

    const W = canvas.width  = canvas.offsetWidth  || 200;
    const H = canvas.height = 130;
    const cx = W / 2, cy = H - 20;
    const r  = Math.min(W, H) * 0.7;

    ctx.clearRect(0, 0, W, H);

    // Track
    ctx.beginPath(); ctx.arc(cx, cy, r, Math.PI, 2 * Math.PI);
    ctx.lineWidth = 14; ctx.strokeStyle = '#f3f4f6'; ctx.stroke();

    // Value arc
    const pct   = Math.min(Math.max((value - min) / (max - min), 0), 1);
    const color = pct < 0.6 ? '#10b981' : pct < 0.85 ? '#f59e0b' : '#ef4444';
    ctx.beginPath(); ctx.arc(cx, cy, r, Math.PI, Math.PI + pct * Math.PI);
    ctx.lineWidth = 14; ctx.strokeStyle = color;
    ctx.lineCap = 'round'; ctx.stroke();

    // Value text
    ctx.fillStyle = '#1f2937'; ctx.font = 'bold 1.6rem Inter,sans-serif';
    ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
    ctx.fillText(parseFloat(value).toFixed(1), cx, cy - 15);

    ctx.fillStyle = '#6b7280'; ctx.font = '0.8rem Inter,sans-serif';
    ctx.fillText(label, cx, cy + 8);
}
EOF