<?php
/**
 * NAIGO — Browse Restaurants
 * Consumer page: search, filter by cuisine, view restaurant cards, click to reserve.
 */
require_once __DIR__ . '/../includes/auth_check.php';

$basePath = getBasePath(__FILE__);
$baseUrl = getBaseUrl();
$currentPage = 'browse_restaurants';
$pageTitle = 'Browse Restaurants';
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
            <h1 class="page-title">Browse Restaurants</h1>
            <p class="page-subtitle">Discover and book your next dining experience</p>

            <!-- Filters -->
            <div class="filter-bar">
                <div class="filter-group">
                    <div class="search-input-wrap">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search restaurants..." class="search-input">
                    </div>
                </div>
                <div class="filter-group">
                    <select id="cuisineFilter" class="filter-select">
                        <option value="">All Cuisines</option>
                    </select>
                </div>
            </div>

            <!-- Restaurant Grid -->
            <div class="restaurant-grid" id="restaurantGrid">
                <div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Loading restaurants...</div>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

async function loadRestaurants() {
    const search = document.getElementById('searchInput').value;
    const cuisine = document.getElementById('cuisineFilter').value;
    const grid = document.getElementById('restaurantGrid');
    grid.innerHTML = '<div class="loading-spinner"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';

    try {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (cuisine) params.append('cuisine', cuisine);
        const res = await fetch(`${BASE_URL}/php/database/restaurants_list.php?${params}`);
        const data = await res.json();

        if (!data.success || !data.restaurants.length) {
            grid.innerHTML = '<div class="empty-state"><i class="fa-solid fa-store" style="font-size:2rem;margin-bottom:0.75rem;color:var(--text-muted);"></i><h3>No restaurants found</h3><p>Try adjusting your search or filters.</p></div>';
            return;
        }

        // Populate cuisine filter
        if (data.cuisines) {
            const cf = document.getElementById('cuisineFilter');
            const current = cf.value;
            cf.innerHTML = '<option value="">All Cuisines</option>';
            data.cuisines.forEach(c => {
                cf.innerHTML += `<option value="${c}" ${c === current ? 'selected' : ''}>${c}</option>`;
            });
        }

        grid.innerHTML = data.restaurants.map(r => `
            <div class="restaurant-card card-interactive">
                <div class="restaurant-card-img">
                    ${r.image_path ? `<img src="${BASE_URL}/${r.image_path}" alt="${r.name}">` : '<div class="no-img"><i class="fa-solid fa-utensils"></i></div>'}
                </div>
                <div class="restaurant-card-body">
                    <div class="restaurant-card-header">
                        <h3>${r.name}</h3>
                        <span class="restaurant-rating"><i class="fa-solid fa-star"></i> ${parseFloat(r.rating).toFixed(1)}</span>
                    </div>
                    <p class="restaurant-cuisine"><i class="fa-solid fa-tag"></i> ${r.cuisine_type || 'General'}</p>
                    <p class="restaurant-address"><i class="fa-solid fa-location-dot"></i> ${r.address || 'Address not available'}</p>
                    <div class="restaurant-meta">
                        <span><i class="fa-solid fa-clock"></i> ${r.opening_time?.slice(0,5)} – ${r.closing_time?.slice(0,5)}</span>
                        <span class="price-range">${r.price_range || '$$'}</span>
                    </div>
                    <a href="${BASE_URL}/php/forms/make_reservation.php?restaurant_id=${r.id}" class="btn-primary" style="width:100%;margin-top:1rem;text-decoration:none;text-align:center;">
                        <i class="fa-solid fa-calendar-plus"></i> Reserve a Table
                    </a>
                </div>
            </div>
        `).join('');
    } catch (e) {
        grid.innerHTML = '<div class="empty-state"><p>Error loading restaurants.</p></div>';
    }
}

document.getElementById('searchInput').addEventListener('input', debounce(loadRestaurants, 400));
document.getElementById('cuisineFilter').addEventListener('change', loadRestaurants);

function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

loadRestaurants();

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
