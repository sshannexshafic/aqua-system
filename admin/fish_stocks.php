<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

function cleanText($text) {
    $text = preg_replace('/<\?php.*?\?>/s', '', $text);
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/* ---------------- ADD ---------------- */
if ($_POST && isset($_POST['add_stock'])) {

    $stmt = $db->prepare("
        INSERT INTO fish_stocks 
        (pond_id, species, quantity, avg_weight, stocking_date, source, cost_per_fish, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([
        $_POST['pond_id'], $_POST['species'], $_POST['quantity'],
        $_POST['avg_weight'], $_POST['stocking_date'],
        $_POST['source'], $_POST['cost_per_fish'] ?? 0,
        $_POST['notes']
    ])) {
        $message = "Fish stock added successfully!";
    } else {
        $error = "Error adding fish stock.";
    }
}

/* ---------------- EDIT ---------------- */
if ($_POST && isset($_POST['edit_stock'])) {

    $stmt = $db->prepare("
        UPDATE fish_stocks 
        SET pond_id=?, species=?, quantity=?, avg_weight=?, stocking_date=?, source=?, cost_per_fish=?, notes=?
        WHERE id=?
    ");

    if ($stmt->execute([
        $_POST['pond_id'], $_POST['species'], $_POST['quantity'],
        $_POST['avg_weight'], $_POST['stocking_date'],
        $_POST['source'], $_POST['cost_per_fish'] ?? 0,
        $_POST['notes'], $_POST['stock_id']
    ])) {
        $message = "Fish stock updated successfully!";
    } else {
        $error = "Error updating fish stock.";
    }
}

/* ---------------- DELETE ---------------- */
if ($_POST && isset($_POST['delete_stock'])) {
    $stmt = $db->prepare("DELETE FROM fish_stocks WHERE id=?");
    $stmt->execute([$_POST['stock_id']]);
    $message = "Fish stock deleted.";
}

/* ---------------- DATA ---------------- */
$ponds = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();

$fish_stocks = $db->query("
    SELECT fs.*, p.name AS pond_name
    FROM fish_stocks fs
    JOIN ponds p ON fs.pond_id = p.id
    ORDER BY fs.stocking_date DESC
")->fetchAll();

$total_fish = 0;
$total_value = 0;
$species_list = [];

foreach ($fish_stocks as $s) {
    $total_fish += (int)$s['quantity'];
    $total_value += (int)$s['quantity'] * (float)$s['cost_per_fish'];
    $species_list[] = $s['species'];
}

$species_list = array_unique($species_list);

include '../includes/header.php';
?>

<style>
.container-card{
    background:#fff;
    padding:18px;
    border-radius:14px;
    box-shadow:0 6px 20px rgba(0,0,0,0.06);
    margin-bottom:20px;
}

.table-wrap{
    overflow-x:auto;
    border-radius:12px;
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:14px;
}

th, td{
    padding:12px;
    text-align:left;
    border-bottom:1px solid #eee;
    white-space:nowrap;
}

th{
    background:#f9fafb;
    font-weight:600;
}

.badge{
    background:#e0f2fe;
    padding:4px 10px;
    border-radius:999px;
    font-size:12px;
}

.btn{
    padding:6px 10px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}

.edit-btn{ background:#dbeafe; }
.delete-btn{ background:#fee2e2; }

.grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
}
input, select{
    padding:10px;
    border:1px solid #ddd;
    border-radius:8px;
}
</style>

<h1>🐟 Fish Stocks Management</h1>
<p>Manage all fish stocks across ponds</p>

<?php if($message): ?>
<div class="container-card" style="background:#ecfdf5;color:#065f46;">
<?= $message ?>
</div>
<?php endif; ?>

<?php if($error): ?>
<div class="container-card" style="background:#fef2f2;color:#991b1b;">
<?= $error ?>
</div>
<?php endif; ?>

<!-- STATS --><br>
<div class="grid container-card">
<div>🐟 Total Fish<br><b><?= number_format($total_fish) ?></b></div>
<div>🏞 Ponds<br><b><?= count(array_unique(array_column($fish_stocks,'pond_id'))) ?></b></div>
<div>🧬 Species<br><b><?= count($species_list) ?></b></div>
<div>💰 Value<br><b>UGX <?= number_format($total_value) ?></b></div>
</div>

<!-- ADD FORM -->
<div class="container-card">
<h3>➕ Add Fish Stock</h3>

<form method="POST" class="grid">

<select name="pond_id" required>
<option value="">Select Pond</option>
<?php foreach($ponds as $p): ?>
<option value="<?= $p['id'] ?>"><?= $p['name'] ?></option>
<?php endforeach; ?>
</select>

<input name="species" placeholder="Species" required>
<input name="quantity" type="number" placeholder="Quantity" required>
<input name="avg_weight" type="number" step="0.01" placeholder="Avg Weight">

<input name="stocking_date" type="date" required>
<input name="source" placeholder="Source">
<input name="cost_per_fish" type="number" placeholder="Cost">
<input name="notes" placeholder="Notes">

<button name="add_stock" class="btn" style="grid-column:span 4;background:#10b981;color:#fff;">
🐟 Add Stock
</button>

</form>
</div>

<!-- TABLE -->
<div class="container-card">
<h3>📋 All Fish Stocks (<?= count($fish_stocks) ?>)</h3>

<div class="table-wrap">
<table>
<thead>
<tr>
<th>Pond</th>
<th>Species</th>
<th>Qty</th>
<th>Weight</th>
<th>Value</th>
<th>Source</th>
<th>Date</th>
<th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach($fish_stocks as $s): ?>
<tr>

<td>🏞 <?= htmlspecialchars($s['pond_name']) ?></td>

<td><span class="badge">🐟 <?= htmlspecialchars($s['species']) ?></span></td>

<td><?= number_format($s['quantity']) ?></td>
<td><?= $s['avg_weight'] ?> kg</td>
<td><b>UGX <?= number_format($s['quantity'] * $s['cost_per_fish']) ?></b></td>
<td><?= cleanText($s['source']) ?: '—' ?></td>
<td><?= date('d M Y', strtotime($s['stocking_date'])) ?></td>

<td>

<button class="btn edit-btn"
onclick='openEdit(<?= json_encode($s) ?>)'>
✏️
</button>

<form method="POST" style="display:inline;">
<input type="hidden" name="stock_id" value="<?= $s['id'] ?>">
<button name="delete_stock" class="btn delete-btn"
onclick="return confirm('Delete this record?')">
🗑️
</button>
</form>

</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);">
<div style="background:#fff;width:420px;margin:8% auto;padding:20px;border-radius:12px;">

<h3>✏️ Edit Stock</h3>

<form method="POST">
<input type="hidden" name="stock_id" id="e_id">

<select name="pond_id" id="e_pond"></select>

<input name="species" id="e_species">
<input name="quantity" id="e_qty">
<input name="avg_weight" id="e_weight">
<input name="stocking_date" id="e_date" type="date">
<input name="source" id="e_source">
<input name="cost_per_fish" id="e_cost">
<input name="notes" id="e_notes">

<br><br>

<button name="edit_stock" class="btn" style="background:#10b981;color:#fff;">💾 Save</button>
<button type="button" onclick="closeEdit()" class="btn">Cancel</button>

</form>

</div>
</div>

<script>
function openEdit(s){
    document.getElementById('editModal').style.display='block';

    document.getElementById('e_id').value = s.id;
    document.getElementById('e_species').value = s.species;
    document.getElementById('e_qty').value = s.quantity;
    document.getElementById('e_weight').value = s.avg_weight;
    document.getElementById('e_date').value = s.stocking_date?.substring(0,10);
    document.getElementById('e_source').value = s.source || '';
    document.getElementById('e_cost').value = s.cost_per_fish || 0;
    document.getElementById('e_notes').value = s.notes || '';

    let pond = document.getElementById('e_pond');
    pond.innerHTML = `<?php foreach($ponds as $p){ echo "<option value='{$p['id']}'>{$p['name']}</option>"; } ?>`;
    pond.value = s.pond_id;
}

function closeEdit(){
    document.getElementById('editModal').style.display='none';
}
</script>

<?php include '../includes/footer.php'; ?>