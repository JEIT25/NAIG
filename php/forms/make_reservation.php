<?php
/**
 * NAIGO — Make Reservation
 * Multi-step: select date/time/party → pick table → confirm.
 */
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('consumer');

$basePath = getBasePath(__FILE__);
$baseUrl = getBaseUrl();
$currentPage = 'browse_restaurants';
$pageTitle = 'Make a Reservation';

$restaurantId = intval($_GET['restaurant_id'] ?? 0);
$restaurant = null;
if ($restaurantId) {
    require_once __DIR__ . '/../database/db_connect.php';
    $stmt = $conn->prepare("SELECT * FROM restaurants WHERE id = ? AND is_active = 1");
    $stmt->bind_param('i', $restaurantId);
    $stmt->execute();
    $restaurant = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
}
if (!$restaurant) {
    header('Location: browse_restaurants.php');
    exit;
}
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
            <a href="browse_restaurants.php" style="color:var(--primary-color);font-weight:600;text-decoration:none;font-size:0.9rem;display:inline-flex;align-items:center;gap:0.35rem;margin-bottom:1rem;">
                <i class="fa-solid fa-arrow-left"></i> Back to Restaurants
            </a>

            <div class="reservation-form-container">
                <!-- Restaurant Info Header -->
                <div class="restaurant-header">
                    <div class="restaurant-header-info">
                        <h1 class="page-title"><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                        <p class="restaurant-details">
                            <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($restaurant['cuisine_type']); ?></span>
                            <span><i class="fa-solid fa-clock"></i> <?php echo substr($restaurant['opening_time'], 0, 5); ?> – <?php echo substr($restaurant['closing_time'], 0, 5); ?></span>
                            <span><i class="fa-solid fa-star" style="color:#c8a951;"></i> <?php echo number_format($restaurant['rating'], 1); ?></span>
                            <span><?php echo $restaurant['price_range']; ?></span>
                        </p>
                        <?php if ($restaurant['description']): ?>
                            <p style="color:var(--text-muted);margin-top:0.5rem;font-size:0.9rem;"><?php echo htmlspecialchars($restaurant['description']); ?></p>
                        <?php
endif; ?>
                    </div>
                </div>

                <!-- Reservation Form -->
                <form id="reservationForm" class="reservation-form">
                    <input type="hidden" name="restaurant_id" value="<?php echo $restaurant['id']; ?>">

                    <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                        <div class="form-group">
                            <label for="resDate">Date <span style="color:#dc2626;">*</span></label>
                            <input type="date" id="resDate" name="reservation_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="resTime">Time <span style="color:#dc2626;">*</span></label>
                            <input type="time" id="resTime" name="reservation_time" required
                                   min="<?php echo substr($restaurant['opening_time'], 0, 5); ?>"
                                   max="<?php echo substr($restaurant['closing_time'], 0, 5); ?>">
                        </div>
                        <div class="form-group">
                            <label for="partySize">Party Size <span style="color:#dc2626;">*</span></label>
                            <input type="number" id="partySize" name="party_size" min="1" max="20" value="2" required>
                        </div>
                    </div>

                    <button type="button" id="checkTablesBtn" class="btn-secondary" style="margin-bottom:1.5rem;">
                        <i class="fa-solid fa-magnifying-glass"></i> Check Available Tables
                    </button>

                    <!-- Available Tables -->
                    <div id="tablesSection" style="display:none;">
                        <h3 style="font-family:var(--font-heading);margin-bottom:1rem;">Available Tables</h3>
                        <div id="tablesGrid" class="tables-grid"></div>
                        <input type="hidden" id="selectedTableId" name="table_id">
                    </div>

                    <div class="form-group" style="margin-top:1.5rem;">
                        <label for="specialRequests">Special Requests <span style="color:var(--primary-color);font-size:0.8rem;">(optional)</span></label>
                        <textarea id="specialRequests" name="special_requests" rows="3" placeholder="Any dietary needs, celebrations, or preferences..."></textarea>
                    </div>

                    <div id="formMessage" style="margin-bottom:1rem;"></div>

                    <button type="submit" id="submitReservation" class="btn-primary" style="width:100%;padding:0.85rem;" disabled>
                        <i class="fa-solid fa-calendar-check"></i> Confirm Reservation
                    </button>
                </form>
            </div>
        </main>
    </div>

<script>
const BASE_URL = '<?php echo $baseUrl; ?>';
const restaurantId = <?php echo $restaurant['id']; ?>;
let selectedTableId = null;

// Check available tables
document.getElementById('checkTablesBtn').addEventListener('click', async () => {
    const date = document.getElementById('resDate').value;
    const time = document.getElementById('resTime').value;
    const partySize = document.getElementById('partySize').value;
    const msg = document.getElementById('formMessage');
    msg.innerHTML = '';

    if (!date || !time || !partySize) {
        msg.innerHTML = '<span style="color:var(--error-color);">Please fill in date, time, and party size.</span>';
        return;
    }

    const section = document.getElementById('tablesSection');
    const grid = document.getElementById('tablesGrid');
    grid.innerHTML = '<div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Checking...</div>';
    section.style.display = 'block';
    selectedTableId = null;
    document.getElementById('selectedTableId').value = '';
    document.getElementById('submitReservation').disabled = true;

    try {
        const params = new URLSearchParams({ restaurant_id: restaurantId, date, time, party_size: partySize });
        const res = await fetch(`${BASE_URL}/php/database/available_tables.php?${params}`);
        const data = await res.json();

        if (!data.success || !data.tables.length) {
            grid.innerHTML = '<div class="empty-state"><p>No tables available for your selection. Try a different date, time, or party size.</p></div>';
            return;
        }

        grid.innerHTML = data.tables.map(t => `
            <div class="table-option" data-id="${t.id}" onclick="selectTable(${t.id}, this)">
                <div class="table-option-icon"><i class="fa-solid fa-chair"></i></div>
                <div class="table-option-info">
                    <strong>Table ${t.table_number}</strong>
                    <span>${t.location} · ${t.capacity} seats</span>
                </div>
            </div>
        `).join('');
    } catch (e) {
        grid.innerHTML = '<div class="empty-state"><p>Error checking availability.</p></div>';
    }
});

function selectTable(id, el) {
    document.querySelectorAll('.table-option').forEach(t => t.classList.remove('selected'));
    el.classList.add('selected');
    selectedTableId = id;
    document.getElementById('selectedTableId').value = id;
    document.getElementById('submitReservation').disabled = false;
}

// Submit reservation
document.getElementById('reservationForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = document.getElementById('formMessage');
    msg.innerHTML = '';
    const btn = document.getElementById('submitReservation');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Booking...';

    const formData = new FormData(e.target);

    try {
        const res = await fetch(`${BASE_URL}/php/database/reservation_create.php`, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.success) {
            msg.innerHTML = '<span style="color:var(--success-color);font-weight:600;"><i class="fa-solid fa-circle-check"></i> Reservation created successfully! Redirecting...</span>';
            setTimeout(() => {
                window.location.href = `${BASE_URL}/php/forms/my_reservations.php`;
            }, 2000);
        } else {
            msg.innerHTML = `<span style="color:var(--error-color);">${data.error || 'Error creating reservation.'}</span>`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Confirm Reservation';
        }
    } catch (e) {
        msg.innerHTML = '<span style="color:var(--error-color);">Network error. Please try again.</span>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-calendar-check"></i> Confirm Reservation';
    }
});

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
