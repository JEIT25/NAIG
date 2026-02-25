<?php
/**
 * Superadmin/Admin - Approval Requests (AMORA-style)
 * Review and approve/reject requests (admins are limited to registration approvals).
 */
require_once __DIR__ . '/../../../../includes/auth_check.php';
require_once __DIR__ . '/../../../../includes/path_helper.php';

if (!in_array($user['role'] ?? '', ['superadmin', 'admin'])) {
    header('Location: ' . getBaseUrl() . '/php/auth/dashboard.php');
    exit;
}

$basePath = getBasePath(__FILE__);
$currentPage = 'requests';
$pageTitle = 'Requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - FoodGrab</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=management.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../../../../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="page-header">
                <h1><i class="fa-solid fa-clipboard-check"></i> Requests</h1>
                <p>View request history and approve or reject pending actions.</p>
            </div>
            <div class="approvals-toolbar">
                <div class="search-container">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search..." value="">
                </div>
                <div class="status-filters">
                    <button type="button" class="status-filter" data-status="all"><i class="fa-solid fa-list"></i> All</button>
                    <button type="button" class="status-filter active" data-status="pending"><i class="fa-solid fa-clock"></i> Pending</button>
                    <button type="button" class="status-filter" data-status="approved"><i class="fa-solid fa-check-circle"></i> Approved</button>
                    <button type="button" class="status-filter" data-status="rejected"><i class="fa-solid fa-times-circle"></i> Rejected</button>
                </div>
            </div>
            <div id="approvals-container" class="approvals-container">
                <div class="loading-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading...</p></div>
            </div>
            <div id="pagination" class="pagination"></div>
        </main>
    </div>
    <?php include __DIR__ . '/../../../../includes/layout/notification_modal.php'; ?>
    <?php include __DIR__ . '/../../../../includes/layout/review_approval_modal.php'; ?>
    <script>
        window.API_BASE = '<?php echo getBaseUrl(); ?>/php/database/';
    </script>
    <script src="<?php echo $basePath; ?>js/serve_asset.php?file=superadmin_approvals.js"></script>
    <?php include __DIR__ . '/../../../../includes/layout/footer.php'; ?>
</body>
</html>
