<?php
/**
 * NAIGO — My Reservations
 * Consumer: list own reservations with filters, cancel pending/confirmed.
 */
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('consumer');

$basePath = getBasePath(__FILE__);
$baseUrl = getBaseUrl();
$currentPage = 'my_reservations';
$pageTitle = 'My Reservations';
?>
<!DOCTYPE html>
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
    <?php $showSidebarToggle = true;
include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <h1 class="page-title">My Reservations</h1>
            <p class="page-subtitle">View and manage your dining reservations</p>

            <!-- Filters -->
            <div class="filter-bar">
                <div class="filter-group">
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                </div>
                <a href="<?php echo $baseUrl; ?>/php/forms/browse_restaurants.php" class="btn-primary" style="text-decoration:none;">
                    <i class="fa-solid fa-plus"></i> New Reservation
                </a>
            </div>

            <!-- Reservations List -->
            <div id="reservationsList" class="reservations-list">
                <div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
            </div>

            <div id="pagination" class="pagination-controls"></div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

<script>
const BASE_URL = '<?php echo $baseUrl; ?>';
let currentPage = 1;

async function loadReservations(page = 1) {
    currentPage = page;
    const status = document.getElementById('statusFilter').value;
    const list = document.getElementById('reservationsList');
    list.innerHTML = '<div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

    try {
        const params = new URLSearchParams({ page, limit: 10 });
        if (status) params.append('status', status);
        const res = await fetch(`${BASE_URL}/php/database/reservation_list.php?${params}`, { credentials: 'same-origin' });
        const data = await res.json();

        if (!data.success || !data.reservations.length) {
            list.innerHTML = '<div class="empty-state"><i class="fa-solid fa-calendar" style="font-size:2rem;margin-bottom:0.75rem;color:var(--text-muted);"></i><h3>No reservations found</h3><p>Start by browsing restaurants and booking a table.</p><a href="' + BASE_URL + '/php/forms/browse_restaurants.php" class="btn-primary" style="text-decoration:none;margin-top:0.5rem;">Browse Restaurants</a></div>';
            document.getElementById('pagination').innerHTML = '';
            return;
        }

        list.innerHTML = data.reservations.map(r => {
            const dateStr = new Date(r.reservation_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const timeStr = r.reservation_time?.slice(0, 5);
            const canCancel = ['pending', 'confirmed'].includes(r.status);
            return `
                <div class="reservation-card">
                    <div class="reservation-card-left">
                        <div class="reservation-date-badge">
                            <span class="date-month">${new Date(r.reservation_date).toLocaleDateString('en-US', { month: 'short' })}</span>
                            <span class="date-day">${new Date(r.reservation_date).getDate()}</span>
                        </div>
                    </div>
                    <div class="reservation-card-body">
                        <h3>${r.restaurant_name}</h3>
                        <p><i class="fa-solid fa-clock"></i> ${timeStr} · <i class="fa-solid fa-users"></i> ${r.party_size} guests</p>
                        ${r.table_number ? `<p><i class="fa-solid fa-chair"></i> Table ${r.table_number}</p>` : ''}
                        ${r.special_requests ? `<p class="special-req"><i class="fa-solid fa-comment"></i> ${r.special_requests}</p>` : ''}
                    </div>
                    <div class="reservation-card-right">
                        <span class="status-badge status-${r.status}">${r.status.replace('_', ' ')}</span>
                        ${canCancel ? `<button class="btn-cancel" onclick="cancelReservation(${r.id})"><i class="fa-solid fa-xmark"></i> Cancel</button>` : ''}
                    </div>
                </div>
            `;
        }).join('');

        // Pagination
        const pag = document.getElementById('pagination');
        if (data.total_pages > 1) {
            let html = `<span class="pagination-info">Page ${data.page} of ${data.total_pages}</span>`;
            if (data.page > 1) html += `<button class="btn-sm btn-secondary" onclick="loadReservations(${data.page - 1})">Prev</button>`;
            if (data.page < data.total_pages) html += `<button class="btn-sm btn-primary" onclick="loadReservations(${data.page + 1})">Next</button>`;
            pag.innerHTML = html;
        } else {
            pag.innerHTML = '';
        }
    } catch (e) {
        list.innerHTML = '<div class="empty-state"><p>Error loading reservations.</p></div>';
    }
}

async function cancelReservation(id) {
    if (!confirm('Are you sure you want to cancel this reservation?')) return;
    try {
        const fd = new FormData();
        fd.append('reservation_id', id);
        const res = await fetch(`${BASE_URL}/php/database/reservation_cancel.php`, { method: 'POST', body: fd, credentials: 'same-origin' });
        const data = await res.json();
        if (data.success) {
            loadReservations(currentPage);
        } else {
            alert(data.error || 'Failed to cancel reservation.');
        }
    } catch (e) {
        alert('Network error.');
    }
}

document.getElementById('statusFilter').addEventListener('change', () => loadReservations(1));
loadReservations();

// Sidebar toggle
const toggle = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.dashboard-sidebar');
const overlay = document.getElementById('sidebarOverlay');
if (toggle && sidebar) {
    toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
    overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });
}
</script>
</body>
</html>
