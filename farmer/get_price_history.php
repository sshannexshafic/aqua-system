<?php
require_once '../includes/auth.php';
require_once '../includes/db_connection.php';
requireRole('farmer');

$database = new Database();
$db = $database->getConnection();
$farmer_id = $_SESSION['user_id'];

$feed_type = isset($_GET['feed_type']) ? trim($_GET['feed_type']) : '';

if (empty($feed_type)) {
    echo "<p style='color:red;'>Invalid feed type.</p>";
    exit;
}

$stmt = $db->prepare("
    SELECT old_price, new_price, changed_at 
    FROM feed_price_history 
    WHERE farmer_id = ? AND feed_type = ? 
    ORDER BY changed_at DESC
");

$stmt->execute([$farmer_id, $feed_type]);
$history = $stmt->fetchAll();
?>

<?php if (count($history) > 0): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Old Price</th>
                <th>New Price</th>
                <th>Change</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($history as $h): ?>
            <tr>
                <td><?= date('d M Y H:i', strtotime($h['changed_at'])) ?></td>
                <td>
                    <?= $h['old_price'] !== null ? 'UGX ' . number_format($h['old_price'], 2) : '<em>New Entry</em>' ?>
                </td>
                <td><strong>UGX <?= number_format($h['new_price'], 2) ?></strong></td>
                <td>
                    <?php 
                    if ($h['old_price'] !== null) {
                        $change = $h['new_price'] - $h['old_price'];
                        $color = $change >= 0 ? 'green' : 'red';
                        echo "<span style='color:$color;'>UGX " . number_format($change, 2) . "</span>";
                    } else {
                        echo "<span style='color:green;'>New Price</span>";
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p><strong>No price history</strong> yet for this feed type.</p>
    <p style="color:#6b7280;">The first time you update the price, history will start showing here.</p>
<?php endif; ?>