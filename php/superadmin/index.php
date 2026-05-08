<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('superadmin');

// Fetch Stats
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

$res = $conn->query("SELECT COUNT(*) as n FROM restaurants");
if ($row = $res->fetch_assoc()) $stats['restaurants'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM restaurant_tables");
if ($row = $res->fetch_assoc()) $stats['tables'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM reservations");
if ($row = $res->fetch_assoc()) $stats['reservations_total'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM reservations WHERE status = 'pending'");
if ($row = $res->fetch_assoc()) $stats['reservations_pending'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM reservations WHERE status = 'confirmed'");
if ($row = $res->fetch_assoc()) $stats['reservations_confirmed'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM users WHERE role = 'consumer'");
if ($row = $res->fetch_assoc()) $stats['consumers'] = $row['n'];

// Fetch Latest Pending Requests (Limit 5)
$requests = $conn->query("
    SELECT * FROM (
        SELECT r.id, r.requester_id, r.target_id, r.reason, 'block' as request_type, r.created_at, u.username as target_username, u.email as target_email, u.role as target_role
        FROM user_block_requests r
        JOIN users u ON r.target_id = u.id
        WHERE r.status = 'pending'
        UNION ALL
        SELECT a.id, a.requested_by as requester_id, a.target_id, 'Admin Registration' as reason, 'registration' as request_type, a.created_at, u.username as target_username, u.email as target_email, u.role as target_role
        FROM approvals a
        JOIN users u ON a.target_id = u.id
        WHERE a.status = 'pending' AND a.target_type = 'user'
    ) AS reqs ORDER BY created_at DESC LIMIT 5
");

// Fetch Latest Logs (Limit 5)
$logs = $conn->query("SELECT l.*, u.firstName, u.lastName, u.username, u.role
                      FROM login_logs l
                      JOIN users u ON l.user_id = u.id
                      ORDER BY l.log_time DESC
                      LIMIT 5");

$pageTitle = 'Superadmin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NAIGO</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-card); padding: 1.25rem; border-radius: 12px; box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1rem; transition: transform 0.2s; border: 1px solid var(--border-light); }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; color: white; }
        
        .bg-teal    { background: linear-gradient(135deg, #1a5653, #0f3533); }
        .bg-gold    { background: linear-gradient(135deg, #c8a951, #b8963e); }
        .bg-blue    { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .bg-emerald { background: linear-gradient(135deg, #059669, #047857); }
        .bg-amber   { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .bg-purple  { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .stat-info h3 { margin: 0; font-size: 1.6rem; font-weight: 800; color: var(--text-heading); letter-spacing: -0.5px; }
        .stat-info p  { margin: 0; color: var(--text-muted); font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'superadmin_dashboard';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <h1 class="page-title">Dashboard Overview</h1>
            <p class="page-subtitle">Manage NAIGO system statistics and view recent activity logs.</p>

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

            <!-- Dashboard Widgets -->
            <div class="dashboard-widgets" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">

                <!-- Recent Requests -->
                <div class="widget-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: 14px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2 class="section-title" style="margin:0; font-size: 1.05rem; font-weight:700;"><i class="fa-solid fa-clipboard-check" style="color:var(--primary-color); margin-right:8px;"></i> Pending Requests</h2>
                        <a href="requests.php" class="small-link" style="font-size: 0.85rem; color: var(--primary-color); font-weight:600;">View All</a>
                    </div>
                    <?php if ($requests && $requests->num_rows > 0): ?>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--bg-body); text-align: left; color: var(--text-muted); font-size:0.78rem; text-transform:uppercase;">
                                    <th style="padding: 0.6rem 0.5rem;">User</th>
                                    <th style="padding: 0.6rem 0.5rem;">Type</th>
                                    <th style="padding: 0.6rem 0.5rem;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $requests->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid var(--border-light);">
                                        <td style="padding: 0.65rem 0.5rem;">
                                            <strong style="color:var(--text-heading);"><?php echo htmlspecialchars($r['target_username'] ?? 'Unknown'); ?></strong><br>
                                            <small class="muted"><?php echo htmlspecialchars($r['target_email'] ?? 'No email'); ?></small>
                                        </td>
                                        <td style="padding: 0.65rem 0.5rem;">
                                            <span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight:700; text-transform:uppercase;"><?php echo htmlspecialchars($r['request_type']); ?></span>
                                        </td>
                                        <td style="padding: 0.65rem 0.5rem; color: var(--text-muted); font-size: 0.82rem;">
                                            <?php echo date('M j', strtotime($r['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="padding:2rem; text-align:center;">
                            <i class="fa-solid fa-clipboard-check" style="font-size:1.5rem; opacity:0.3; margin-bottom:0.5rem;"></i>
                            <p class="muted" style="font-size: 0.9rem; margin:0;">No pending requests.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Latest Logs -->
                <div class="widget-card" style="background: var(--bg-card); padding: 1.5rem; border-radius: 14px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2 class="section-title" style="margin:0; font-size: 1.05rem; font-weight:700;"><i class="fa-solid fa-list-check" style="color:var(--primary-color); margin-right:8px;"></i> Recent Login Logs</h2>
                        <a href="logs.php" class="small-link" style="font-size: 0.85rem; color: var(--primary-color); font-weight:600;">View All</a>
                    </div>
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--bg-body); text-align: left; color: var(--text-muted); font-size:0.78rem; text-transform:uppercase;">
                                    <th style="padding: 0.6rem 0.5rem;">Action</th>
                                    <th style="padding: 0.6rem 0.5rem;">User</th>
                                    <th style="padding: 0.6rem 0.5rem;">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($l = $logs->fetch_assoc()): ?>
                                    <tr style="border-bottom: 1px solid var(--border-light);">
                                        <td style="padding: 0.65rem 0.5rem;">
                                            <span style="color: <?php echo $l['action'] === 'login' ? '#059669' : '#dc2626'; ?>; font-weight: 700; font-size:0.75rem; text-transform:uppercase;">
                                                <?php echo ucfirst($l['action']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.65rem 0.5rem; font-weight:600; color:var(--text-heading);">
                                            <?php echo htmlspecialchars($l['username']); ?>
                                        </td>
                                        <td style="padding: 0.65rem 0.5rem; color: var(--text-muted); font-size:0.82rem;">
                                            <?php echo date('M j H:i', strtotime($l['log_time'])); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="padding:2rem; text-align:center;">
                            <i class="fa-solid fa-list" style="font-size:1.5rem; opacity:0.3; margin-bottom:0.5rem;"></i>
                            <p class="muted" style="margin:0; font-size:0.9rem;">No logs found.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>
</body>
</html>
