<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_stock'])) {
        $data = [
            $_POST['pond_id'],
            $_POST['species'],
            $_POST['quantity'],
            $_POST['avg_weight'],
            $_POST['stocking_date']
        ];
        $stmt = $db->prepare("INSERT INTO fish_stocks (pond_id, species, quantity, avg_weight, stocking_date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute($data)) {
            $message = "Fish stock added successfully!";
        } else {
            $error = "Error adding fish stock!";
        }
    }
    
    if (isset($_POST['delete_stock'])) {
        $stmt = $db->prepare("DELETE FROM fish_stocks WHERE id = ?");
        if ($stmt->execute([$_POST['stock_id']])) {
            $message = "Fish stock deleted successfully!";
        }
    }
}

// Fetch all data
$ponds = $db->query("SELECT id, name FROM ponds ORDER BY name")->fetchAll();
$fish_stocks = $db->query("
    SELECT fs.*, p.name as pond_name 
    FROM fish_stocks fs 
    JOIN ponds p ON fs.pond_id = p.id 
    ORDER BY fs.stocking_date DESC
")->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="admin-page">
    <div class="page-header">
        <h1><i class="icon-fish"></i> Fish Stocks Management</h1>
        <?php if ($message) echo "<div class='success'>$message</div>"; ?>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    </div>

    <!-- Add New Fish Stock Form -->
    <div class="card">
        <h3>Add New Fish Stock</h3>
        <form method="POST" class="form-grid">
            <select name="pond_id" required>
                <option value="">Select Pond</option>
                <?php foreach($ponds as $pond): ?>
                <option value="<?php echo $pond['id']; ?>"><?php echo $pond['name']; ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="text" name="species" placeholder="Species (e.g. Tilapia)" required>
            <input type="number" name="quantity" placeholder="Quantity" required>
            <input type="number" step="0.01" name="avg_weight" placeholder="Avg Weight (kg)" required>
            <input type="date" name="stocking_date" required>
            
            <button type="submit" name="add_stock" class="btn-primary">Add Stock</button>
        </form>
    </div>

    <!-- Fish Stocks Table -->
    <div class="card">
        <h3>All Fish Stocks (<?php echo count($fish_stocks); ?>)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pond</th>
                        <th>Species</th>
                        <th>Quantity</th>
                        <th>Avg Weight</th>
                        <th>Stock Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($fish_stocks as $stock): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stock['pond_name']); ?></td>
                        <td><?php echo htmlspecialchars($stock['species']); ?></td>
                        <td class="number"><?php echo number_format($stock['quantity']); ?></td>
                        <td><?php echo $stock['avg_weight']; ?> kg</td>
                        <td><?php echo date('M j, Y', strtotime($stock['stocking_date'])); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this stock?')">
                                <input type="hidden" name="stock_id" value="<?php echo $stock['id']; ?>">
                                <button type="submit" name="delete_stock" class="btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>