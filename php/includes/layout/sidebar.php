<?php
/**
 * NAIGO Role-based sidebar — Reservation System
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($user)) {
    $user = $_SESSION['user'] ?? null;
}
$userRole = $user['role'] ?? 'consumer';

if (!function_exists('getBaseUrl')) {
    function getBaseUrl()
    {
        return 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/NAIG';
    }
}
$baseUrl = getBaseUrl();

function isActive($page, $current)
{
    return $current === $page ? 'active' : '';
}
$currentPage = $currentPage ?? '';

$fullName = trim(
    htmlspecialchars($user['firstName'] ?? 'Guest') . ' ' .
    htmlspecialchars($user['middleInitial'] ?? '') . ' ' .
    htmlspecialchars($user['lastName'] ?? '')
);
$initials = '';
if (!empty($user['firstName']))
    $initials .= strtoupper($user['firstName'][0]);
if (!empty($user['lastName']))
    $initials .= strtoupper($user['lastName'][0]);
if (!$initials)
    $initials = 'G';

$roleBadgeColors = [
    'superadmin' => 'background:#ede9fe; color:#7c3aed;',
    'admin' => 'background:#fef3c7; color:#d97706;',
    'consumer' => 'background:#f0fdfa; color:#1a5653;',
];
$roleIcon = [
    'superadmin' => 'fa-user-gear', // System Manager (was crown)
    'admin' => 'fa-user-tie', // Restaurant Manager (was shield)
    'consumer' => 'fa-utensils', // Diner (was user)
];
$badgeStyle = $roleBadgeColors[$userRole] ?? 'background:#f3f4f6; color:#6b7280;';
$icon = $roleIcon[$userRole] ?? 'fa-user';
?>
<aside class="dashboard-sidebar">
    <div class="profile-section" style="display:flex; flex-direction:column; align-items:center; gap:0.5rem; padding:1.25rem 1rem;">
        <div style="width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg, #1a5653, #0f3533); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.15rem; font-weight:700; box-shadow:0 2px 8px rgba(26,86,83,0.25);">
            <?php echo $initials; ?>
        </div>
        <p style="margin:0; font-weight:700; font-size:0.95rem; color:var(--text-heading); text-align:center; line-height:1.3;"><?php echo $fullName; ?></p>
        <span style="display:inline-flex; align-items:center; gap:0.3rem; padding:0.2rem 0.65rem; border-radius:20px; font-size:0.75rem; font-weight:600; <?php echo $badgeStyle; ?>">
            <i class="fa-solid <?php echo $icon; ?>" style="font-size:0.7rem;"></i>
            <?php echo ucfirst($userRole); ?>
        </span>
    </div>
    <nav class="sidebar-menu">
        <?php if ($userRole === 'superadmin'): ?>
            <p class="muted small" style="padding: 0 1rem; margin-bottom: 0.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Overview</p>
            <a href="<?php echo $baseUrl; ?>/php/superadmin/index.php" class="<?php echo isActive('superadmin_dashboard', $currentPage); ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>

            <p class="muted small" style="padding: 0 1rem; margin: 1rem 0 0.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Management</p>
            <a href="<?php echo $baseUrl; ?>/php/admin/restaurants.php" class="<?php echo isActive('admin_restaurants', $currentPage); ?>"><i class="fa-solid fa-store"></i> Restaurants</a>
            <a href="<?php echo $baseUrl; ?>/php/admin/tables.php" class="<?php echo isActive('admin_tables', $currentPage); ?>"><i class="fa-solid fa-chair"></i> Tables</a>
            <a href="<?php echo $baseUrl; ?>/php/admin/reservations.php" class="<?php echo isActive('admin_reservations', $currentPage); ?>"><i class="fa-solid fa-calendar-check"></i> Reservations</a>

            <p class="muted small" style="padding: 0 1rem; margin: 1rem 0 0.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Users & Roles</p>
            <a href="<?php echo $baseUrl; ?>/php/superadmin/users.php" class="<?php echo isActive('superadmin_users', $currentPage); ?>"><i class="fa-solid fa-users-gear"></i> Manage Accounts</a>
            <a href="<?php echo $baseUrl; ?>/php/superadmin/requests.php" class="<?php echo isActive('superadmin_requests', $currentPage); ?>"><i class="fa-solid fa-clipboard-check"></i> Manage Requests</a>
            <a href="<?php echo $baseUrl; ?>/php/superadmin/logs.php" class="<?php echo isActive('superadmin_logs', $currentPage); ?>"><i class="fa-solid fa-list-check"></i>Logs</a>

        <?php
elseif ($userRole === 'admin'): ?>
            <p class="muted small" style="padding: 0 1rem; margin-bottom: 0.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Overview</p>
            <a href="<?php echo $baseUrl; ?>/php/admin/index.php" class="<?php echo isActive('admin_dashboard', $currentPage); ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>

            <p class="muted small" style="padding: 0 1rem; margin: 1rem 0 0.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Restaurant</p>
            <a href="<?php echo $baseUrl; ?>/php/admin/restaurants.php" class="<?php echo isActive('admin_restaurants', $currentPage); ?>"><i class="fa-solid fa-store"></i> Restaurants</a>
            <a href="<?php echo $baseUrl; ?>/php/admin/tables.php" class="<?php echo isActive('admin_tables', $currentPage); ?>"><i class="fa-solid fa-chair"></i> Tables</a>
            <a href="<?php echo $baseUrl; ?>/php/admin/reservations.php" class="<?php echo isActive('admin_reservations', $currentPage); ?>"><i class="fa-solid fa-calendar-check"></i> Reservations</a>

            <p class="muted small" style="padding: 0 1rem; margin: 1rem 0 0.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">People</p>
            <a href="<?php echo $baseUrl; ?>/php/admin/consumers.php" class="<?php echo isActive('admin_consumers', $currentPage); ?>"><i class="fa-solid fa-users"></i> Manage Consumers</a>
            <a href="<?php echo $baseUrl; ?>/php/admin/admin_requests.php" class="<?php echo isActive('admin_requests', $currentPage); ?>"><i class="fa-solid fa-clipboard-check"></i> Manage Requests</a>

        <?php
else: ?>
            <!-- Consumer -->
            <p class="muted small" style="padding: 0 1rem; margin-bottom: 0.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Menu</p>
            <a href="<?php echo $baseUrl; ?>/php/auth/dashboard.php" class="<?php echo isActive('consumer_dashboard', $currentPage);
    echo isActive('dashboard', $currentPage); ?>"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="<?php echo $baseUrl; ?>/php/forms/browse_restaurants.php" class="<?php echo isActive('browse_restaurants', $currentPage); ?>"><i class="fa-solid fa-utensils"></i> Browse Restaurants</a>
            <a href="<?php echo $baseUrl; ?>/php/forms/my_reservations.php" class="<?php echo isActive('my_reservations', $currentPage); ?>"><i class="fa-solid fa-calendar-days"></i> My Reservations</a>
        <?php
endif; ?>

        <p class="muted small" style="padding: 0 1rem; margin: 1rem 0 0.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">Account</p>
        <a href="<?php echo $baseUrl; ?>/php/forms/profile.php" class="<?php echo isActive('profile', $currentPage); ?>"><i class="fa-solid fa-id-card"></i> My Profile</a>
        <a href="<?php echo $baseUrl; ?>/php/auth/logout.php" class="danger-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
</aside>
