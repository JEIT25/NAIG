<?php
/**
 * NAIGO Dashboard - All roles.
 * Consumer: reservation stats, latest restaurants, latest reservations, quick links.
 * Admin/Superadmin: management links.
 */
require_once __DIR__ . '/../includes/auth_check.php';

if (isset($_POST['logout_action'])) {
    require_once __DIR__ . '/../database/db_connect.php';
    $logStmt = $conn->prepare("INSERT INTO login_logs (user_id, action) VALUES (?, 'logout')");
    $logStmt->bind_param('s', $_SESSION['user']['id']);
    $logStmt->execute();
    $logStmt->close();
    $conn->close();
    session_destroy();
    header('Location: login.php');
    exit;
}

$basePath = getBasePath(__FILE__);
$baseUrl = getBaseUrl();
$currentPage = 'dashboard';
$pageTitle = 'Dashboard';

// Consumer dashboard data
$consumerStats = null;
$latestReservations = [];
$latestRestaurants = [];

if ($userRole === 'consumer' && isset($user['id'])) {
    require_once __DIR__ . '/../database/db_connect.php';
    $uid = $user['id'];
    $consumerStats = [
        'total_reservations' => 0,
        'pending_reservations' => 0,
        'confirmed_reservations' => 0,
        'completed_reservations' => 0,
        'upcoming' => null,
    ];

    $stmt = $conn->prepare("SELECT COUNT(*) as n FROM reservations WHERE user_id = ?");
    if ($stmt) { $stmt->bind_param('s', $uid); $stmt->execute(); $consumerStats['total_reservations'] = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0); $stmt->close(); }

    $stmt = $conn->prepare("SELECT COUNT(*) as n FROM reservations WHERE user_id = ? AND status = 'pending'");
    if ($stmt) { $stmt->bind_param('s', $uid); $stmt->execute(); $consumerStats['pending_reservations'] = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0); $stmt->close(); }

    $stmt = $conn->prepare("SELECT COUNT(*) as n FROM reservations WHERE user_id = ? AND status = 'confirmed'");
    if ($stmt) { $stmt->bind_param('s', $uid); $stmt->execute(); $consumerStats['confirmed_reservations'] = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0); $stmt->close(); }

    $stmt = $conn->prepare("SELECT COUNT(*) as n FROM reservations WHERE user_id = ? AND status = 'completed'");
    if ($stmt) { $stmt->bind_param('s', $uid); $stmt->execute(); $consumerStats['completed_reservations'] = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0); $stmt->close(); }

    $stmt = $conn->prepare("SELECT r.*, rest.name AS restaurant_name FROM reservations r JOIN restaurants rest ON rest.id = r.restaurant_id WHERE r.user_id = ? AND r.status IN ('pending','confirmed') AND r.reservation_date >= CURDATE() ORDER BY r.reservation_date ASC, r.reservation_time ASC LIMIT 1");
    if ($stmt) { $stmt->bind_param('s', $uid); $stmt->execute(); $res = $stmt->get_result()->fetch_assoc(); if ($res) $consumerStats['upcoming'] = $res; $stmt->close(); }

    $stmt = $conn->prepare("SELECT r.*, rest.name AS restaurant_name FROM reservations r JOIN restaurants rest ON rest.id = r.restaurant_id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 5");
    if ($stmt) { $stmt->bind_param('s', $uid); $stmt->execute(); $result = $stmt->get_result(); while ($row = $result->fetch_assoc()) { $latestReservations[] = $row; } $stmt->close(); }

    $rstmt = $conn->query("SELECT * FROM restaurants WHERE is_active = 1 ORDER BY created_at DESC LIMIT 4");
    if ($rstmt) { while ($row = $rstmt->fetch_assoc()) { $latestRestaurants[] = $row; } }

    $conn->close();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - NAIGO</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=reservations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php
$showSidebarToggle = true;
include __DIR__ . '/../includes/layout/navbar.php';
?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <h1 class="page-title">Welcome, <?php echo htmlspecialchars($user['firstName']); ?>!</h1>
            <p class="page-subtitle"><?php
if ($userRole === 'consumer')
    echo 'Browse restaurants, make reservations, and manage your dining experiences.';
elseif ($userRole === 'admin')
    echo 'Manage restaurants, tables, and reservations.';
else
    echo 'Manage users, roles, and system settings.';
?></p>

            <?php if ($userRole === 'consumer'): ?>
                <?php if ($consumerStats !== null): ?>
                <section class="dashboard-stats" aria-label="Reservation summary">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon" aria-hidden="true"><i class="fa-solid fa-calendar-check"></i></div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $consumerStats['total_reservations']; ?></span>
                                <span class="stat-label">Total Reservations</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-pending" aria-hidden="true"><i class="fa-solid fa-clock"></i></div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $consumerStats['pending_reservations']; ?></span>
                                <span class="stat-label">Pending</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-confirmed" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $consumerStats['confirmed_reservations']; ?></span>
                                <span class="stat-label">Confirmed</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-completed" aria-hidden="true"><i class="fa-solid fa-flag-checkered"></i></div>
                            <div class="stat-content">
                                <span class="stat-value"><?php echo $consumerStats['completed_reservations']; ?></span>
                                <span class="stat-label">Completed</span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($consumerStats['upcoming'])):
                        $up = $consumerStats['upcoming']; ?>
                    <div class="latest-order-section">
                        <h2 class="section-title">Upcoming Reservation</h2>
                        <a href="<?php echo $baseUrl; ?>/php/forms/my_reservations.php" class="latest-order-card card-interactive">
                            <div class="latest-order-info">
                                <span class="latest-order-restaurant"><?php echo htmlspecialchars($up['restaurant_name']); ?></span>
                                <span class="latest-order-meta">
                                    <?php echo date('M d, Y', strtotime($up['reservation_date'])); ?> at
                                    <?php echo date('g:i A', strtotime($up['reservation_time'])); ?>
                                    &middot; <?php echo $up['party_size']; ?> guests
                                    &middot; <span class="status-badge status-<?php echo $up['status']; ?>"><?php echo ucfirst($up['status']); ?></span>
                                </span>
                            </div>
                            <span class="latest-order-link-text">View details <i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="latest-order-section">
                        <h2 class="section-title">Upcoming Reservation</h2>
                        <div class="empty-state">
                            <i class="fa-solid fa-calendar-plus" style="font-size:2rem;margin-bottom:0.75rem;color:var(--text-muted);"></i>
                            <p>No upcoming reservations.</p>
                            <a href="<?php echo $baseUrl; ?>/php/forms/browse_restaurants.php" class="btn-primary">Browse Restaurants</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if (!empty($latestRestaurants)): ?>
                <section class="dashboard-section" aria-label="Latest Restaurants">
                    <div class="dashboard-section-header">
                        <h2 class="section-title">Latest Restaurants</h2>
                        <a href="<?php echo $baseUrl; ?>/php/forms/browse_restaurants.php" class="section-link">View All <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div class="dashboard-restaurants-grid">
                        <?php foreach ($latestRestaurants as $rest): ?>
                        <div class="restaurant-card card-interactive">
                            <div class="restaurant-card-img">
                                <?php if (!empty($rest['image_path'])): ?>
                                    <img src="<?php echo $baseUrl . '/' . htmlspecialchars($rest['image_path']); ?>" alt="<?php echo htmlspecialchars($rest['name']); ?>">
                                <?php else: ?>
                                    <div class="no-img"><i class="fa-solid fa-utensils"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="restaurant-card-body">
                                <div class="restaurant-card-header">
                                    <h3><?php echo htmlspecialchars($rest['name']); ?></h3>
                                    <span class="restaurant-rating"><i class="fa-solid fa-star"></i> <?php echo number_format((float)$rest['rating'], 1); ?></span>
                                </div>
                                <p class="restaurant-cuisine"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($rest['cuisine_type'] ?: 'General'); ?></p>
                                <p class="restaurant-address"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($rest['address'] ?: 'Address not available'); ?></p>
                                <div class="restaurant-meta">
                                    <span><i class="fa-solid fa-clock"></i> <?php echo substr($rest['opening_time'], 0, 5); ?> &ndash; <?php echo substr($rest['closing_time'], 0, 5); ?></span>
                                    <span class="price-range"><?php echo htmlspecialchars($rest['price_range'] ?: '$$'); ?></span>
                                </div>
                                <a href="<?php echo $baseUrl; ?>/php/forms/make_reservation.php?restaurant_id=<?php echo $rest['id']; ?>" class="btn-primary" style="width:100%;margin-top:1rem;text-decoration:none;text-align:center;display:block;">
                                    <i class="fa-solid fa-calendar-plus"></i> Reserve a Table
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!empty($latestReservations)): ?>
                <section class="dashboard-section" aria-label="Latest Reservations">
                    <div class="dashboard-section-header">
                        <h2 class="section-title">Latest Reservations</h2>
                        <a href="<?php echo $baseUrl; ?>/php/forms/my_reservations.php" class="section-link">View All <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div class="reservations-list">
                        <?php foreach ($latestReservations as $rv): ?>
                        <div class="reservation-card">
                            <div class="reservation-date-badge">
                                <span class="date-month"><?php echo date('M', strtotime($rv['reservation_date'])); ?></span>
                                <span class="date-day"><?php echo date('d', strtotime($rv['reservation_date'])); ?></span>
                            </div>
                            <div class="reservation-card-body">
                                <h3><?php echo htmlspecialchars($rv['restaurant_name']); ?></h3>
                                <p><i class="fa-solid fa-clock"></i> <?php echo date('g:i A', strtotime($rv['reservation_time'])); ?></p>
                                <p><i class="fa-solid fa-users"></i> <?php echo $rv['party_size']; ?> guests</p>
                            </div>
                            <div class="reservation-card-right">
                                <span class="status-badge status-<?php echo $rv['status']; ?>"><?php echo ucfirst($rv['status']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>



            <?php elseif ($userRole === 'admin'): ?>
                <h2 class="section-title quick-links-title">Management</h2>
                <div class="quick-links-grid">
                    <a href="<?php echo $baseUrl; ?>/php/admin/restaurants.php" class="quick-link card-interactive">
                        <div class="ql-icon"><i class="fa-solid fa-store"></i></div>
                        <span class="ql-label">Manage Restaurants</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/php/admin/tables.php" class="quick-link card-interactive">
                        <div class="ql-icon"><i class="fa-solid fa-chair"></i></div>
                        <span class="ql-label">Manage Tables</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/php/admin/reservations.php" class="quick-link card-interactive">
                        <div class="ql-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <span class="ql-label">All Reservations</span>
                    </a>
                </div>

            <?php else: ?>
                <h2 class="section-title quick-links-title">System Administration</h2>
                <div class="quick-links-grid">
                    <a href="<?php echo $baseUrl; ?>/php/superadmin/users.php" class="quick-link card-interactive">
                        <div class="ql-icon"><i class="fa-solid fa-users"></i></div>
                        <span class="ql-label">Users &amp; Roles</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/php/superadmin/requests.php" class="quick-link card-interactive">
                        <div class="ql-icon"><i class="fa-solid fa-user-shield"></i></div>
                        <span class="ql-label">Block Requests</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/php/superadmin/logs.php" class="quick-link card-interactive">
                        <div class="ql-icon"><i class="fa-solid fa-list"></i></div>
                        <span class="ql-label">Login Logs</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/php/admin/restaurants.php" class="quick-link card-interactive">
                        <div class="ql-icon"><i class="fa-solid fa-store"></i></div>
                        <span class="ql-label">Restaurants</span>
                    </a>
                    <a href="<?php echo $baseUrl; ?>/php/admin/reservations.php" class="quick-link card-interactive">
                        <div class="ql-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <span class="ql-label">All Reservations</span>
                    </a>
                </div>
            <?php endif; ?>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

<script>
// Sidebar toggle
const toggle = document.getElementById('sidebarToggle');
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
