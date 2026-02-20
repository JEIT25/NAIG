<?php
/**
 * NAIGO Navbar: fixed at top, full width.
 * Expects $basePath, $baseUrl (or getBaseUrl()).
 * Reservation system — no cart icon.
 */
if (!isset($basePath)) {
    $basePath = isset($base) ? rtrim($base, '/') . '/' : '../../';
}
if (!function_exists('getBaseUrl') && !isset($baseUrl)) {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/NAIG';
}
if (!isset($baseUrl)) {
    $baseUrl = getBaseUrl();
}
$navUserRole = $userRole ?? ($_SESSION['user']['role'] ?? '');
?>
<nav class="navbar" role="navigation" aria-label="Main">
    <div class="navbar-inner">
        <div class="navbar-left">
            <a href="<?php echo htmlspecialchars($baseUrl); ?>/php/auth/dashboard.php" class="navbar-brand" style="display:flex;align-items:center;gap:0.75rem;text-decoration:none;color:inherit;">
                <div class="navbar-logo-icon" aria-hidden="true"><i class="fa-solid fa-concierge-bell"></i></div>
                <span class="navbar-text">NAIGO <span class="navbar-subtext">Online Restaurant Reservation</span></span>
            </a>
        </div>
        <div class="navbar-right">
            <?php if (!empty($showSidebarToggle)): ?>
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
            <?php
endif; ?>
            <form action="<?php echo htmlspecialchars($baseUrl); ?>/php/auth/dashboard.php" method="POST" style="display:inline;">
                <input type="hidden" name="logout_action" value="1">
                <button type="submit" class="nav-link"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
            </form>
        </div>
    </div>
</nav>
