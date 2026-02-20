<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../database/db_connect.php';

// ── Stats ─────────────────────────────────────────────
$stats = [
    'restaurants' => 0,
    'tables' => 0,
    'reservations_total' => 0,
    'reservations_pending' => 0,
    'reservations_confirmed' => 0,
    'consumers' => 0,
];

$res = $conn->query("SELECT COUNT(*) as n FROM restaurants WHERE is_active = 1");
if ($row = $res->fetch_assoc())
    $stats['restaurants'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM restaurant_tables");
if ($row = $res->fetch_assoc())
    $stats['tables'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM reservations");
if ($row = $res->fetch_assoc())
    $stats['reservations_total'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM reservations WHERE status = 'pending'");
if ($row = $res->fetch_assoc())
    $stats['reservations_pending'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM reservations WHERE status = 'confirmed'");
if ($row = $res->fetch_assoc())
    $stats['reservations_confirmed'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM users WHERE role = 'consumer'");
if ($row = $res->fetch_assoc())
    $stats['consumers'] = $row['n'];

// ── Latest Reservations (5) ───────────────────────────
$latestReservations = $conn->query(
    "SELECT r.id, r.reservation_date, r.reservation_time, r.party_size, r.status, r.created_at,
            u.firstName, u.lastName, rest.name AS restaurant_name
     FROM reservations r
     JOIN users u ON r.user_id = u.id
     JOIN restaurants rest ON r.restaurant_id = rest.id
     ORDER BY r.created_at DESC LIMIT 5"
);

// ── Latest Tables (5) ─────────────────────────────────
$latestTables = $conn->query(
    "SELECT rt.id, rt.table_number, rt.capacity, rt.location, rt.is_available,
            rest.name AS restaurant_name
     FROM restaurant_tables rt
     JOIN restaurants rest ON rt.restaurant_id = rest.id
     ORDER BY rt.created_at DESC LIMIT 5"
);

// ── My Admin Requests (5) ─────────────────────────────
$myRequests = $conn->query(
    "SELECT * FROM admin_creation_requests
     WHERE requested_by = '" . $conn->real_escape_string($_SESSION['user']['id']) . "'
     ORDER BY created_at DESC LIMIT 5"
);

$user = $_SESSION['user'];
$userRole = $user['role'] ?? 'admin';
$basePath = '../../';
$pageTitle = 'Admin Dashboard';
$showSidebarToggle = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - NAIGO</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Stat card grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-card); padding: 1.25rem; border-radius: 12px; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid var(--border-light); }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
        .bg-teal    { background: linear-gradient(135deg, #1a5653, #0f3533); color: #fff; }
        .bg-gold    { background: linear-gradient(135deg, #c8a951, #b8963e); color: #fff; }
        .bg-blue    { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
        .bg-emerald { background: linear-gradient(135deg, #059669, #047857); color: #fff; }
        .bg-amber   { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; }
        .bg-purple  { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: #fff; }
        .stat-info h3 { margin: 0; font-size: 1.6rem; font-weight: 800; color: var(--text-heading); letter-spacing: -0.5px; }
        .stat-info p  { margin: 0; color: var(--text-muted); font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Widget cards */
        .dashboard-widgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 1.5rem; }
        .widget-card { background: var(--bg-card); padding: 1.5rem; border-radius: 14px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
        .section-title { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-heading); display: flex; align-items: center; gap: 0.6rem; }
        .widget-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(26,86,83,0.08); border-radius: 8px; color: var(--primary-color); font-size: 0.95rem; }
        .view-all { font-size: 0.82rem; color: var(--primary-color); font-weight: 600; text-decoration: none; }
        .view-all:hover { text-decoration: underline; }

        /* Mini table */
        .mini-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .mini-table thead tr { border-bottom: 2px solid var(--bg-body); text-align: left; color: var(--text-muted); }
        .mini-table th { padding: 0.6rem 0.5rem; font-weight: 600; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .mini-table td { padding: 0.65rem 0.5rem; border-bottom: 1px solid var(--border-light); vertical-align: middle; }
        .mini-table tr:last-child td { border-bottom: none; }
        .mini-table .cell-primary { font-weight: 600; color: var(--text-heading); }
        .mini-table .cell-muted   { color: var(--text-muted); font-size: 0.82rem; }
        .mini-table .cell-mono    { font-family: 'JetBrains Mono', monospace; font-weight: 600; color: var(--primary-color); font-size: 0.85rem; }

        /* Availability dot */
        .avail-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 0.35rem; vertical-align: middle; }
        .avail-dot.avail  { background: #059669; }
        .avail-dot.unavail { background: #dc2626; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php $currentPage = 'admin_dashboard';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <h1 class="page-title">Admin Dashboard</h1>
            <p class="page-subtitle">Manage restaurants, tables, and reservations at a glance.</p>

            <!-- ── Stat Cards ─────────────────────── -->
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-teal"><i class="fa-solid fa-store"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['restaurants']; ?></h3>
                        <p>Restaurants</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-gold"><i class="fa-solid fa-chair"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['tables']; ?></h3>
                        <p>Tables</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['reservations_total']; ?></h3>
                        <p>Reservations</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-amber"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['reservations_pending']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-emerald"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['reservations_confirmed']; ?></h3>
                        <p>Confirmed</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $stats['consumers']; ?></h3>
                        <p>Consumers</p>
                    </div>
                </div>
            </section>

            <!-- ── Widgets Grid ───────────────────── -->
            <div class="dashboard-widgets">

                <!-- Latest Reservations -->
                <div class="widget-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <div class="widget-icon"><i class="fa-solid fa-calendar-check"></i></div>
                            Latest Reservations
                        </h2>
                        <a href="reservations.php" class="view-all">View All</a>
                    </div>
                    <?php if ($latestReservations && $latestReservations->num_rows > 0): ?>
                        <table class="mini-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Guest</th>
                                    <th>Restaurant</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($r = $latestReservations->fetch_assoc()):
        $sClass = match ($r['status']) {
                'confirmed' => 'status-confirmed',
                'completed' => 'status-completed',
                'cancelled' => 'status-trash',
                'no_show' => 'status-no-show',
                default => 'status-pending',
            };
?>
                                <tr>
                                    <td class="cell-mono">#<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="cell-primary"><?php echo htmlspecialchars($r['firstName'] . ' ' . $r['lastName']); ?></td>
                                    <td class="cell-muted"><?php echo htmlspecialchars($r['restaurant_name']); ?></td>
                                    <td class="cell-muted"><?php echo date('M j', strtotime($r['reservation_date'])); ?>, <?php echo date('g:i A', strtotime($r['reservation_time'])); ?></td>
                                    <td><span class="status-badge no-dot <?php echo $sClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $r['status'])); ?></span></td>
                                </tr>
                            <?php
    endwhile; ?>
                            </tbody>
                        </table>
                    <?php
else: ?>
                        <div class="empty-state" style="padding: 2rem;">
                            <i class="fa-solid fa-calendar-xmark" style="font-size: 1.5rem; color: var(--text-muted); opacity: 0.5; margin-bottom: 0.5rem;"></i>
                            <p style="color: var(--text-muted); margin: 0;">No reservations yet.</p>
                        </div>
                    <?php
endif; ?>
                </div>

                <!-- Latest Tables -->
                <div class="widget-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <div class="widget-icon"><i class="fa-solid fa-chair"></i></div>
                            Latest Tables
                        </h2>
                        <a href="tables.php" class="view-all">View All</a>
                    </div>
                    <?php if ($latestTables && $latestTables->num_rows > 0): ?>
                        <table class="mini-table">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Restaurant</th>
                                    <th>Capacity</th>
                                    <th>Location</th>
                                    <th>Available</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($t = $latestTables->fetch_assoc()): ?>
                                <tr>
                                    <td class="cell-primary"><?php echo htmlspecialchars($t['table_number']); ?></td>
                                    <td class="cell-muted"><?php echo htmlspecialchars($t['restaurant_name']); ?></td>
                                    <td><i class="fa-solid fa-user" style="color: var(--text-muted); font-size: 0.75rem; margin-right: 0.25rem;"></i><?php echo $t['capacity']; ?></td>
                                    <td class="cell-muted" style="text-transform: capitalize;"><?php echo htmlspecialchars($t['location']); ?></td>
                                    <td>
                                        <span class="avail-dot <?php echo $t['is_available'] ? 'avail' : 'unavail'; ?>"></span>
                                        <?php echo $t['is_available'] ? 'Yes' : 'No'; ?>
                                    </td>
                                </tr>
                            <?php
    endwhile; ?>
                            </tbody>
                        </table>
                    <?php
else: ?>
                        <div class="empty-state" style="padding: 2rem;">
                            <i class="fa-solid fa-chair" style="font-size: 1.5rem; color: var(--text-muted); opacity: 0.5; margin-bottom: 0.5rem;"></i>
                            <p style="color: var(--text-muted); margin: 0;">No tables created yet.</p>
                        </div>
                    <?php
endif; ?>
                </div>

                <!-- My Admin Requests -->
                <div class="widget-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <div class="widget-icon"><i class="fa-solid fa-file-contract"></i></div>
                            My Requests
                        </h2>
                        <a href="admin_requests.php" class="view-all">View All</a>
                    </div>
                    <?php if ($myRequests && $myRequests->num_rows > 0): ?>
                        <table class="mini-table">
                            <thead>
                                <tr>
                                    <th>Target User</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($req = $myRequests->fetch_assoc()):
        $reqClass = match ($req['status']) {
                'approved' => 'status-ok',
                'rejected' => 'status-trash',
                default => 'status-pending',
            };
?>
                                <tr>
                                    <td>
                                        <span class="cell-primary"><?php echo htmlspecialchars($req['target_username']); ?></span>
                                        <span class="cell-muted" style="display: block;"><?php echo htmlspecialchars($req['target_email']); ?></span>
                                    </td>
                                    <td class="cell-muted" style="text-transform: capitalize;"><?php echo htmlspecialchars($req['target_role']); ?></td>
                                    <td><span class="status-badge no-dot <?php echo $reqClass; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                    <td class="cell-muted"><?php echo date('M j', strtotime($req['created_at'])); ?></td>
                                </tr>
                            <?php
    endwhile; ?>
                            </tbody>
                        </table>
                    <?php
else: ?>
                        <div class="empty-state" style="padding: 2rem;">
                            <i class="fa-solid fa-file-circle-check" style="font-size: 1.5rem; color: var(--text-muted); opacity: 0.5; margin-bottom: 0.5rem;"></i>
                            <p style="color: var(--text-muted); margin: 0;">No requests submitted yet.</p>
                        </div>
                    <?php
endif; ?>
                </div>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

<script>
const toggle  = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.dashboard-sidebar');
const overlay = document.getElementById('sidebarOverlay');
if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });
}
</script>
</body>
</html>
