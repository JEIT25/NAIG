<?php
/**
 * Auth helper: role-based access and redirects (AMORA-style).
 * Use requireLogin() or requireRole() at the top of protected pages.
 * After requireLogin/requireRole: $user, $userRole, hasRole(), isAdmin(), isSuperadmin() available.
 */

if (!function_exists('getBaseUrl')) {
    /**
     * Base URL for the app (no trailing slash).
     * Uses DOCUMENT_ROOT + NAIG so it works on XAMPP.
     */
    function getBaseUrl()
    {
        $doc = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        // If we're in php/forms or php/admin etc., go up to project root
        if (strpos($script, '/NAIG') !== false) {
            return 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . preg_replace('#/php/.*$#', '', $script);
        }
        return 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/NAIG';
    }
}

if (!function_exists('requireLogin')) {
    /** Redirect to login if no session user. */
    function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header('Location: ' . getBaseUrl() . '/php/auth/login.php');
            exit;
        }
        $user = $_SESSION['user'];
        $GLOBALS['user'] = $user;
        $GLOBALS['userRole'] = $user['role'] ?? 'consumer';
        return $user;
    }
}

if (!function_exists('hasRole')) {
    /** Check if current user has a specific role. Admin includes superadmin. */
    function hasRole($requiredRole)
    {
        $role = $GLOBALS['userRole'] ?? ($_SESSION['user']['role'] ?? 'consumer');
        if ($requiredRole === 'admin') {
            return in_array($role, ['admin', 'superadmin'], true);
        }
        return $role === $requiredRole;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        return hasRole('admin');
    }
}

if (!function_exists('isSuperadmin')) {
    function isSuperadmin()
    {
        return ($GLOBALS['userRole'] ?? ($_SESSION['user']['role'] ?? '')) === 'superadmin';
    }
}

if (!function_exists('requireRole')) {
    /**
     * Require login and one of the given roles.
     * @param string|array $allowedRoles e.g. 'superadmin' or ['consumer','admin']
     * @return array user row
     */
    function requireRole($allowedRoles)
    {
        $user = requireLogin();
        $role = $user['role'] ?? 'consumer';
        if (!in_array($role, (array)$allowedRoles, true)) {
            // Redirect to their dashboard
            $base = getBaseUrl();
            header('Location: ' . $base . '/php/auth/dashboard.php');
            exit;
        }
        return $user;
    }
}

if (!function_exists('getDashboardRedirect')) {
    /** Return redirect URL after login based on role. */
    function getDashboardRedirect()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return getBaseUrl() . '/php/auth/login.php';
        }
        return getBaseUrl() . '/php/auth/dashboard.php';
    }
}
