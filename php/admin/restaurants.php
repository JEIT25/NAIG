<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'superadmin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_action'])) {
    session_unset();
    session_destroy();
    header('Location: ' . getBaseUrl() . '/php/forms/login.php');
    exit;
}
$base = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants - Admin</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'admin_restaurants';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <h1 class="page-title">Manage Stores</h1>
            <p class="page-subtitle">Add, edit, or deactivate restaurants.</p>

            <button type="button" id="addRestaurantBtn" class="submitBtn" style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; padding: 0.5rem 1rem;">
                <i class="fa-solid fa-plus"></i> Add Restaurant
            </button>

            <div id="restaurantsList" class="table-container"></div>

            <!-- Edit/Add Modal -->
            <div id="restaurantModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div class="modal-content" style="background:var(--bg-card); padding:1.5rem; border-radius:var(--radius-lg); width:90%; max-width:550px; box-shadow:var(--shadow-lg); max-height:90vh; overflow-y:auto;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <h2 id="modalTitle" style="margin:0; font-size:1.25rem;">Add Restaurant</h2>
                        <button type="button" id="closeModal" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:var(--text-muted);">&times;</button>
                    </div>
                    <form id="restaurantForm">
                        <input type="hidden" name="id" id="rest_id" value="">

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="rest_name" class="input-label">Restaurant Name</label>
                                <input type="text" name="name" id="rest_name" class="input-field" required placeholder="e.g. Burger King">
                            </div>
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="rest_cuisine" class="input-label">Cuisine Type</label>
                                <input type="text" name="cuisine_type" id="rest_cuisine" class="input-field" placeholder="e.g. Italian, Burgers">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="rest_desc" class="input-label">Description</label>
                            <textarea name="description" id="rest_desc" class="input-field" rows="2" placeholder="Brief description..."></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="rest_address" class="input-label">Address</label>
                            <input type="text" name="address" id="rest_address" class="input-field" placeholder="Full address">
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem;">
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="rest_capacity" class="input-label">Capacity</label>
                                <input type="number" name="capacity" id="rest_capacity" class="input-field" value="50">
                            </div>
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="rest_price" class="input-label">Price Range</label>
                                <select name="price_range" id="rest_price" class="input-field">
                                    <option value="$">$ (Cheap)</option>
                                    <option value="$$" selected>$$ (Moderate)</option>
                                    <option value="$$$">$$$ (Expensive)</option>
                                    <option value="$$$$">$$$$ (Luxury)</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label class="input-label">Active?</label>
                                <div style="display:flex; align-items:center; height:42px;">
                                    <input type="checkbox" name="is_active" id="rest_active" value="1" checked style="width:20px; height:20px; cursor:pointer;">
                                </div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="rest_open" class="input-label">Opening Time</label>
                                <input type="time" name="opening_time" id="rest_open" class="input-field" value="09:00">
                            </div>
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="rest_close" class="input-label">Closing Time</label>
                                <input type="time" name="closing_time" id="rest_close" class="input-field" value="22:00">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="rest_image" class="input-label">Image URL (Optional)</label>
                            <input type="url" name="image_path" id="rest_image" class="input-field" placeholder="https://example.com/image.jpg">
                        </div>

                        <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
                            <button type="button" id="cancelModal" class="btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Cancel</button>
                            <button type="submit" class="submitBtn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Message Modal -->
            <div id="messageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1100; align-items:center; justify-content:center;">
                <div style="background:white; padding:1.5rem; border-radius:12px; width:90%; max-width:350px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1);">
                    <div id="msgIconContainer" style="width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                        <i id="msgIcon" class="fa-solid" style="font-size:1.5rem;"></i>
                    </div>
                    <h3 id="msgTitle" style="margin-bottom:0.25rem; font-size: 1.1rem;"></h3>
                    <p id="msgBody" style="color:var(--text-muted); margin-bottom:1.5rem; font-size: 0.9rem;"></p>
                    <button onclick="document.getElementById('messageModal').style.display='none'" class="submitBtn" style="width:100%; padding: 0.5rem;">Okay</button>
                </div>
            </div>

        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>
    <script>
        const api = '<?php echo $base; ?>' + '/php/database';
        let currentPage = 1;
        let totalPages = 1;

        function load(page = 1) {
            currentPage = page;
            fetch(api + '/admin_restaurants.php?page=' + page, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;

                    totalPages = data.pagination?.total_pages || 1;

                    if (!data.restaurants.length) {
                        document.getElementById('restaurantsList').innerHTML = `
                            <div class="empty-state" style="padding: 2rem; text-align: center; border: 2px dashed var(--border-light); border-radius: var(--radius-lg);">
                                <i class="fa-solid fa-store" style="font-size: 2rem; color: var(--border-medium); margin-bottom: 0.5rem;"></i>
                                <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">No restaurants found.</p>
                            </div>`;
                        return;
                    }

                    let html = '<table class="data-table" style="width:100%;"><thead><tr> <th>Image</th> <th>Details</th> <th>Info</th> <th>Status</th> <th style="text-align:right;">Actions</th> </tr></thead><tbody>';

                    data.restaurants.forEach(r => {
                        const icon = r.image_path
                            ? `<img src="${escapeHtml(r.image_path)}" class="table-thumb">`
                            : `<div class="table-thumb"><i class="fa-solid fa-store"></i></div>`;

                        const isActive = r.is_active == 1;
                        const statusBadge = isActive
                            ? `<span class="status-badge no-dot status-ok">Active</span>`
                            : `<span class="status-badge no-dot status-trash">Inactive</span>`;

                        html += `<tr>
                            <td>${icon}</td>
                            <td>
                                <div class="table-primary-text">${escapeHtml(r.name)}</div>
                                <div class="table-secondary-text">${escapeHtml(r.description || 'No description')}</div>
                            </td>
                            <td>
                                <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.15rem;">${escapeHtml(r.cuisine_type || 'General')} • ${escapeHtml(r.price_range || '$$')}</div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-regular fa-clock" style="width:14px;"></i> ${escapeHtml(r.opening_time)} - ${escapeHtml(r.closing_time)}</div>
                            </td>
                            <td>${statusBadge}</td>
                            <td style="text-align:right;">
                                <a href="javascript:void(0)" style="color:var(--primary-color); font-weight:600; font-size:0.85rem; text-decoration:none; cursor:pointer;" onclick='openEditModal(${JSON.stringify(r)})'>Edit</a>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';

                    if (totalPages > 1) {
                         html += `<div class="pagination-controls">
                            <span class="pagination-info">Page ${currentPage} of ${totalPages}</span>
                            <div style="display:flex; gap:0.5rem;">
                                <button class="btn-secondary btn-sm" onclick="load(currentPage-1)" ${currentPage<=1?'disabled':''}>Previous</button>
                                <button class="btn-secondary btn-sm" onclick="load(currentPage+1)" ${currentPage>=totalPages?'disabled':''}>Next</button>
                            </div>
                        </div>`;
                    }

                    document.getElementById('restaurantsList').innerHTML = html;
                });
        }

        function toggleRest(id, status) {
            // ... (keeping toggle logic if needed, though not exposed in UI currently)
        }

        function openEditModal(r) {
            document.getElementById('modalTitle').textContent = 'Edit Restaurant';
            document.getElementById('rest_id').value = r.id;
            document.getElementById('rest_name').value = r.name || '';
            document.getElementById('rest_desc').value = r.description || '';
            document.getElementById('rest_address').value = r.address || '';
            document.getElementById('rest_image').value = r.image_path || '';
            document.getElementById('rest_cuisine').value = r.cuisine_type || '';
            document.getElementById('rest_capacity').value = r.capacity || 50;
            document.getElementById('rest_open').value = r.opening_time || '09:00';
            document.getElementById('rest_close').value = r.closing_time || '22:00';
            document.getElementById('rest_price').value = r.price_range || '$$';
            document.getElementById('rest_active').checked = r.is_active == 1;
            document.getElementById('restaurantModal').style.display = 'flex';
        }

        document.getElementById('addRestaurantBtn').onclick = () => {
            document.getElementById('modalTitle').textContent = 'Add Restaurant';
            document.getElementById('restaurantForm').reset();
            document.getElementById('rest_id').value = '';
            document.getElementById('rest_active').checked = true;
            document.getElementById('restaurantModal').style.display = 'flex';
        };

        const closeEls = [document.getElementById('closeModal'), document.getElementById('cancelModal')];
        closeEls.forEach(el => el.onclick = () => document.getElementById('restaurantModal').style.display = 'none');

        document.getElementById('restaurantForm').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'save');
            // Checkbox handling: if unchecked, it's not in FormData, so let's handle it manually or rely on PHP checking isset
            // But PHP 'save' likely expects 'is_active'.
            if (!document.getElementById('rest_active').checked) {
                fd.append('is_active', 0);
            }

            fetch(api + '/admin_restaurants.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    document.getElementById('restaurantModal').style.display = 'none';
                    if (d.success) {
                        showMessageModal('success', 'Success', 'Restaurant saved successfully.');
                        load(currentPage);
                    } else {
                        showMessageModal('error', 'Error', d.error || 'Could not save restaurant.');
                    }
                })
                .catch(() => {
                     document.getElementById('restaurantModal').style.display = 'none';
                     showMessageModal('error', 'Error', 'Network error occurred.');
                });
        };

        function showMessageModal(type, title, message) {
            const modal = document.getElementById('messageModal');
            const iconContainer = document.getElementById('msgIconContainer');
            const icon = document.getElementById('msgIcon');
            const titleEl = document.getElementById('msgTitle');
            const bodyEl = document.getElementById('msgBody');

            titleEl.textContent = title;
            bodyEl.textContent = message;

            if (type === 'success') {
                iconContainer.style.background = '#dcfce7';
                icon.className = 'fa-solid fa-check';
                icon.style.color = '#16a34a';
                titleEl.style.color = '#16a34a';
            } else {
                iconContainer.style.background = '#fee2e2';
                icon.className = 'fa-solid fa-xmark';
                icon.style.color = '#dc2626';
                titleEl.style.color = '#dc2626';
            }

            modal.style.display = 'flex';
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        load();
    </script>
</body>
</html>
