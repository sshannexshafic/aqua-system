<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// ── ADD STOCK ──
if ($_POST && isset($_POST['add_stock'])) {
    $stmt = $db->prepare("INSERT INTO fish_stocks (pond_id, species, quantity, avg_weight, stocking_date, source, cost_per_fish, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([
        $_POST['pond_id'], $_POST['species'], $_POST['quantity'],
        $_POST['avg_weight'], $_POST['stocking_date'],
        $_POST['source'] ?? '', $_POST['cost_per_fish'] ?? 0, $_POST['notes'] ?? ''
    ])) {
        $message = "Fish stock added successfully!";
    } else {
        $error = "Error adding fish stock.";
    }
}

// ── EDIT STOCK ──
if ($_POST && isset($_POST['edit_stock'])) {
    $stmt = $db->prepare("UPDATE fish_stocks SET pond_id=?, species=?, quantity=?, avg_weight=?, stocking_date=?, source=?, cost_per_fish=?, notes=? WHERE id=?");
    if ($stmt->execute([
        $_POST['pond_id'], $_POST['species'], $_POST['quantity'],
        $_POST['avg_weight'], $_POST['stocking_date'],
        $_POST['source'] ?? '', $_POST['cost_per_fish'] ?? 0,
        $_POST['notes'] ?? '', $_POST['stock_id']
    ])) {
        $message = "Fish stock updated successfully!";
    } else {
        $error = "Error updating fish stock.";
    }
}

// ── DELETE STOCK ──
if ($_POST && isset($_POST['delete_stock'])) {
    $stmt = $db->prepare("DELETE FROM fish_stocks WHERE id=?");
    if ($stmt->execute([$_POST['stock_id']])) {
        $message = "Fish stock deleted.";
    } else {
        $error = "Error deleting fish stock.";
    }
}

// ── FETCH DATA ──
$ponds       = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();
$fish_stocks = $db->query("
    SELECT fs.*, p.name AS pond_name
    FROM fish_stocks fs
    JOIN ponds p ON fs.pond_id = p.id
    ORDER BY fs.stocking_date DESC
")->fetchAll();

$total_fish  = array_sum(array_column($fish_stocks, 'quantity'));
$total_ponds = count(array_unique(array_column($fish_stocks, 'pond_id')));
$species_list = array_unique(array_column($fish_stocks, 'species'));
$total_value = array_sum(array_map(fn($s) => $s['quantity'] * $s['cost_per_fish'], $fish_stocks));
?>
<?php include '../includes/header.php'; ?>

<!-- EDIT MODAL -->
<div id="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:999;backdrop-filter:blur(3px);" onclick="closeModal()"></div>

<div id="modal-edit" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;border-radius:20px;padding:2rem;width:90%;max-width:540px;z-index:1000;box-shadow:0 30px 60px rgba(0,0,0,.3);max-height:90vh;overflow-y:auto;">
    <h3 style="margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem;">✏️ Edit Fish Stock</h3>
    <form method="POST">
        <input type="hidden" name="stock_id" id="edit-stock-id">
        <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Pond *</label>
                <select name="pond_id" id="edit-pond-id" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                    <?php foreach($ponds as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Species *</label>
                    <input type="text" name="species" id="edit-species" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Quantity *</label>
                    <input type="number" name="quantity" id="edit-quantity" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Avg Weight (kg) *</label>
                    <input type="number" step="0.01" name="avg_weight" id="edit-avg-weight" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Stocking Date *</label>
                    <input type="date" name="stocking_date" id="edit-stocking-date" required style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Source / Supplier</label>
                    <input type="text" name="source" id="edit-source" placeholder="e.g. Busolwe Hatchery" style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
                <div>
                    <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Cost per Fish (UGX)</label>
                    <input type="number" step="0.01" name="cost_per_fish" id="edit-cost" style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
                </div>
            </div>
            <div>
                <label style="font-size:.85rem;font-weight:500;display:block;margin-bottom:.3rem;">Notes</label>
                <input type="text" name="notes" id="edit-notes" style="width:100%;padding:.75rem;border:2px solid #e5e7eb;border-radius:10px;">
            </div>
        </div>
        <div style="display:flex;gap:.75rem;">
            <button type="submit" name="edit_stock" class="btn-primary" style="flex:1;padding:.85rem;">💾 Save Changes</button>
            <button type="button" onclick="closeModal()" style="flex:1;padding:.85rem;border:2px solid #e5e7eb;border-radius:10px;background:white;cursor:pointer;font-size:.95rem;">Cancel</button>
        </div>
    </form>
</div>

<div class="admin-page">

    <div class="page-header">
        <span style="font-size:2.5rem;">🐟</span>
        <div>
            <h1>Fish Stocks Management</h1>
            <p style="color:#6b7280;margin:0;">Add, edit and manage all fish stocks across every pond</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="background:#f0fdf4;color:#16a34a;padding:1rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;border:1px solid #bbf7d0;">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:#fef2f2;color:#dc2626;padding:1rem 1.5rem;border-radius:10px;margin-bottom:1.5rem;border:1px solid #fecaca;">⚠️ <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue"><h3><?php echo number_format($total_fish); ?></h3><p>Total Fish</p></div>
        <div class="stat-card green"><h3><?php echo $total_ponds; ?></h3><p>Ponds with Stock</p></div>
        <div class="stat-card orange"><h3><?php echo count($species_list); ?></h3><p>Species</p></div>
        <div class="stat-card purple"><h3>UGX <?php echo number_format($total_value); ?></h3><p>Total Stock Value</p></div>
    </div>

    <!-- Add New Stock -->
    <div class="card">
        <h3 style="margin-bottom:1.25rem;">➕ Add New Fish Stock</h3>
        <form method="POST">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1rem;">
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Pond *</label>
                    <select name="pond_id" required style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                        <option value="">Select Pond</option>
                        <?php foreach($ponds as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Species *</label>
                    <input type="text" name="species" placeholder="e.g. Tilapia" required style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Quantity *</label>
                    <input type="number" name="quantity" placeholder="e.g. 500" required style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Avg Weight (kg) *</label>
                    <input type="number" step="0.01" name="avg_weight" placeholder="e.g. 0.05" required style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Stocking Date *</label>
                    <input type="date" name="stocking_date" required style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Source / Supplier</label>
                    <input type="text" name="source" placeholder="e.g. Busolwe Hatchery" style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Cost per Fish (UGX)</label>
                    <input type="number" step="0.01" name="cost_per_fish" placeholder="e.g. 500" style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
                <div>
                    <label style="display:block;font-weight:500;margin-bottom:.4rem;font-size:.9rem;">Notes</label>
                    <input type="text" name="notes" placeholder="Optional" style="width:100%;padding:.8rem 1rem;border:2px solid #e5e7eb;border-radius:10px;font-size:.95rem;">
                </div>
            </div>
            <button type="submit" name="add_stock" class="btn-primary" style="padding:.85rem 2rem;">🐟 Add Fish Stock</button>
        </form>
    </div>

    <!-- Fish Stocks Table -->
    <div class="card">
        <h3 style="margin-bottom:1.25rem;">All Fish Stocks (<?php echo count($fish_stocks); ?>)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pond</th>
                        <th>Species</th>
                        <th>Quantity</th>
                        <th>Avg Weight</th>
                        <th>Stock Value</th>
                        <th>Source</th>
                        <th>Stocking Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($fish_stocks): foreach($fish_stocks as $s): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($s['pond_name']); ?></strong></td>
                    <td>
                        <span style="background:#eff6ff;color:#2563eb;padding:.25rem .7rem;border-radius:20px;font-size:.82rem;font-weight:500;">
                            🐟 <?php echo htmlspecialchars($s['species']); ?>
                        </span>
                    </td>
                    <td class="number"><?php echo number_format($s['quantity']); ?></td>
                    <td class="number"><?php echo $s['avg_weight']; ?> kg</td>
                    <td class="number">UGX <?php echo number_format($s['quantity'] * $s['cost_per_fish']); ?></td>
                    <td style="color:#6b7280;font-size:.88rem;"><?php echo htmlspecialchars($s['source'] ?? '—'); ?></td>
                    <td><?php echo date('d M Y', strtotime($s['stocking_date'])); ?></td>
                    <td style="white-space:nowrap;">
                        <!-- EDIT BUTTON -->
                        <button onclick="openEdit(<?php echo htmlspecialchars(json_encode($s)); ?>)"
                            style="background:#eff6ff;border:1px solid #bfdbfe;color:#2563eb;padding:.4rem .8rem;border-radius:8px;cursor:pointer;font-size:.82rem;margin-right:.3rem;font-weight:500;">
                            ✏️ Edit
                        </button>
                        <!-- DELETE BUTTON -->
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this fish stock record? This cannot be undone.')">
                            <input type="hidden" name="stock_id" value="<?php echo $s['id']; ?>">
                            <button type="submit" name="delete_stock"
                                style="background:#fef2f2;border:1px solid #fecaca;color:#ef4444;padding:.4rem .8rem;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:500;">
                                🗑️ Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" style="text-align:center;color:#6b7280;padding:3rem;">
                        No fish stocks yet. Add your first stock above.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openEdit(s) {
    document.getElementById('edit-stock-id').value      = s.id;
    document.getElementById('edit-pond-id').value       = s.pond_id;
    document.getElementById('edit-species').value       = s.species;
    document.getElementById('edit-quantity').value      = s.quantity;
    document.getElementById('edit-avg-weight').value    = s.avg_weight;
    document.getElementById('edit-stocking-date').value = s.stocking_date ? s.stocking_date.substring(0,10) : '';
    document.getElementById('edit-source').value        = s.source || '';
    document.getElementById('edit-cost').value          = s.cost_per_fish || 0;
    document.getElementById('edit-notes').value         = s.notes || '';
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('modal-edit').style.display    = 'block';
}

function closeModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('modal-edit').style.display    = 'none';
}

document.addEventListener('keydown', e => { if(e.key === 'Escape') closeModal(); });
</script>

<?php include '../includes/footer.php'; ?>