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
    if (isset($_POST['add_pond'])) {
        $data = [
            $_POST['name'],
            $_POST['location'],
            $_POST['size'],
            $_POST['depth'],
            $_POST['farmer_id']
        ];
        $stmt = $db->prepare("INSERT INTO ponds (name, location, size, depth, farmer_id) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute($data)) {
            $message = "Pond added successfully!";
        } else {
            $error = "Error adding pond!";
        }
    }
    
    if (isset($_POST['delete_pond'])) {
        $stmt = $db->prepare("DELETE FROM ponds WHERE id = ?");
        if ($stmt->execute([$_POST['pond_id']])) {
            $message = "Pond deleted successfully!";
        }
    }
}

// Fetch data
$ponds = $db->query("
    SELECT p.*, u.username as farmer_name 
    FROM ponds p 
    LEFT JOIN users u ON p.farmer_id = u.id 
    ORDER BY p.created_at DESC
")->fetchAll();

$farmers = $db->query("SELECT id, username FROM users WHERE role = 'farmer'")->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<div class="admin-page">
    <div class="page-header">
        <h1><i class="icon-pond"></i> Ponds Management</h1>
        <?php if ($message) echo "<div class='success'>$message</div>"; ?>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    </div>

    <!-- Add New Pond Form -->
    <div class="card">
        <h3>Add New Pond</h3>
        <form method="POST" class="form-grid">
            <input type="text" name="name" placeholder="Pond Name (e.g. Pond A)" required>
            <input type="text" name="location" placeholder="Location (Busolwe)" required>
            <input type="number" step="0.01" name="size" placeholder="Size (m²)" required>
            <input type="number" step="0.01" name="depth" placeholder="Depth (m)" required>
            
            <select name="farmer_id" required>
                <option value="">Assign to Farmer</option>
                <?php foreach($farmers as $farmer): ?>
                <option value="<?php echo $farmer['id']; ?>"><?php echo $farmer['username']; ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" name="add_pond" class="btn-primary">Add Pond</button>
        </form>
    </div>

    <!-- Ponds Table -->
    <div class="card">
        <h3>All Ponds (<?php echo count($ponds); ?>)</h3>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Size</th>
                        <th>Depth</th>
                        <th>Farmer</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ponds as $pond): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($pond['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($pond['location']); ?></td>
                        <td class="number"><?php echo $pond['size']; ?> m²</td>
                        <td><?php echo $pond['depth']; ?> m</td>
                        <td><?php echo $pond['farmer_name'] ?: 'Unassigned'; ?></td>
                        <td><?php echo date('M j, Y', strtotime($pond['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this pond? All data will be lost!')">
                                <input type="hidden" name="pond_id" value="<?php echo $pond['id']; ?>">
                                <button type="submit" name="delete_pond" class="btn-danger btn-sm">Delete</button>
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