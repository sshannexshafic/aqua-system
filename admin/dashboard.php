<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';
require_once '../includes/db_connection.php';

requireRole('admin');

// =============================
// SERVICE CLASS
// =============================
class AdminDashboardService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getDashboardStats(): array {
        return [
            'total_ponds'   => $this->getCount('ponds'),
            'active_ponds'  => $this->getCount('ponds', "WHERE status='active'"),
            'total_fish'    => $this->getSum('harvest_records', 'total_weight'),
            'total_revenue' => $this->getSum('harvest_records', 'total_revenue'),
            'total_expenses'=> $this->getSum('expenses', 'amount'),
            'total_users'   => $this->getCount('users'),
            'open_health'   => $this->getCount('health_records', "WHERE status='open'")
        ];
    }

    public function getRecentHarvests($limit = 8): array {
        $stmt = $this->db->prepare("
            SELECT h.*, p.name AS pond_name
            FROM harvest_records h
            JOIN ponds p ON h.pond_id = p.id
            ORDER BY h.harvest_date DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentWaterQuality($limit = 8): array {
        $stmt = $this->db->prepare("
            SELECT w.*, p.name AS pond_name
            FROM water_quality w
            JOIN ponds p ON w.pond_id = p.id
            ORDER BY w.recorded_at DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsers($limit = 12): array {
        $stmt = $this->db->prepare("
            SELECT id, username, email, role, is_active
            FROM users
            ORDER BY id DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCount($table, $where = ''): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM $table $where")->fetchColumn();
    }

    private function getSum($table, $column): float {
        return (float)$this->db->query("SELECT COALESCE(SUM($column),0) FROM $table")->fetchColumn();
    }
}

// =============================
// INIT
// =============================
$database = new Database();
$db = $database->getConnection();

$service = new AdminDashboardService($db);

$stats = $service->getDashboardStats();
$recent_harvest = $service->getRecentHarvests();
$recent_wq = $service->getRecentWaterQuality();
$all_users = $service->getAllUsers();

$net_profit = $stats['total_revenue'] - $stats['total_expenses'];

include '../includes/header.php';
?>

<style>
.dashboard-wrapper{padding:20px;}

.page-title{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}

.page-title h1{margin:0;font-size:22px;}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:15px;
    margin-bottom:25px;
}

.stat-card{
    background:#fff;
    padding:18px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06);
    text-align:center;
}

.stat-icon{font-size:24px;}

.stat-number{font-size:20px;font-weight:bold;}

.stat-label{font-size:13px;color:#777;}

.card{
    background:#fff;
    padding:18px;
    border-radius:12px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06);
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th, td{
    padding:10px;
    border-bottom:1px solid #eee;
}

th{
    background:#f8f8f8;
    text-align:left;
}

.badge{
    padding:4px 8px;
    border-radius:6px;
    font-size:12px;
}

.active{background:#e6f7ee;color:#2ecc71;}
.inactive{background:#ffeaea;color:#e74c3c;}
</style>

<div class="dashboard-wrapper">

    <div class="page-title">
        <div>
            <h1>🏠 Admin Dashboard</h1>
            <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">🏞️</div>
            <div class="stat-number"><?= $stats['total_ponds'] ?></div>
            <div class="stat-label">Total Ponds</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🐟</div>
            <div class="stat-number"><?= $stats['active_ponds'] ?></div>
            <div class="stat-label">Active Ponds</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🐠</div>
            <div class="stat-number"><?= number_format($stats['total_fish']) ?> kg</div>
            <div class="stat-label">Fish Stock</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-number">UGX <?= number_format($stats['total_revenue']) ?></div>
            <div class="stat-label">Revenue</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">💸</div>
            <div class="stat-number">UGX <?= number_format($stats['total_expenses']) ?></div>
            <div class="stat-label">Expenses</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-number">UGX <?= number_format($net_profit) ?></div>
            <div class="stat-label">Net Profit</div>
        </div>

    </div>

    <!-- RECENT HARVEST -->
    <div class="card">
        <h3>🌾 Recent Harvests</h3>
        <table>
            <tr>
                <th>Pond</th>
                <th>Qty</th>
                <th>Value</th>
                <th>Date</th>
            </tr>

            <?php foreach ($recent_harvest as $h): ?>
            <tr>
                <td>🏞️ <?= htmlspecialchars($h['pond_name'] ?? 'Unknown') ?></td>
                <td><?= number_format($h['total_weight'] ?? 0) ?> kg</td>
                <td>UGX <?= number_format($h['total_value'] ?? 0) ?></td>
                <td><?= htmlspecialchars($h['harvest_date'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- WATER QUALITY -->
    <div class="card">
        <h3>💧 Water Quality</h3>
        <table>
            <tr>
                <th>Pond</th>
                <th>pH</th>
                <th>Temp</th>
                <th>Date</th>
            </tr>

            <?php foreach ($recent_wq as $w): ?>
            <tr>
                <td>🏞️ <?= htmlspecialchars($w['pond_name'] ?? 'Unknown') ?></td>
                <td><?= $w['ph'] ?? '-' ?></td>
                <td><?= $w['temperature'] ?? '-' ?>°C</td>
                <td><?= htmlspecialchars($w['recorded_at'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- USERS -->
    <div class="card">
        <h3>👥 Users</h3>
        <table>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
            </tr>

            <?php foreach ($all_users as $u): ?>
            <tr>
                <td>👤 <?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role'] ?></td>
                <td>
                    <span class="badge <?= $u['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

</div>

<?php include '../includes/footer.php'; ?>