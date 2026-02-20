<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'superadmin']);
$base = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables - Admin</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'admin_tables';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <h1 class="page-title">Manage Tables</h1>
            <p class="page-subtitle">Configure seating for each restaurant.</p>

            <button type="button" id="addTableBtn" class="submitBtn" style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; padding: 0.5rem 1rem;">
                <i class="fa-solid fa-plus"></i> Add Table
            </button>

            <div id="tablesList" class="table-container"></div>

            <!-- Edit/Add Modal -->
            <div id="tableModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div class="modal-content" style="background:var(--bg-card); padding:1.5rem; border-radius:var(--radius-lg); width:90%; max-width:450px; box-shadow:var(--shadow-lg);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <h2 id="modalTitle" style="margin:0; font-size:1.25rem;">Add Table</h2>
                        <button type="button" id="closeModal" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:var(--text-muted);">&times;</button>
                    </div>
                    <form id="tableForm">
                        <input type="hidden" name="id" id="table_id" value="">

                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="restaurant_id" class="input-label">Restaurant</label>
                            <select name="restaurant_id" id="restaurant_id" class="input-field" required>
                                <option value="">Select Restaurant</option>
                            </select>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="table_number" class="input-label">Table Number/Name</label>
                                <input type="text" name="table_number" id="table_number" class="input-field" required placeholder="e.g. T-1, Patio-2">
                            </div>
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label for="capacity" class="input-label">Capacity (Seats)</label>
                                <input type="number" name="capacity" id="capacity" class="input-field" value="2" min="1" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="location" class="input-label">Location</label>
                            <select name="location" id="location" class="input-field">
                                <option value="indoor">Indoor</option>
                                <option value="outdoor">Outdoor</option>
                                <option value="balcony">Balcony</option>
                                <option value="rooftop">Rooftop</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label class="input-label">Status</label>
                            <div style="display:flex; align-items:center; height:42px;">
                                <input type="checkbox" name="is_available" id="is_available" value="1" checked style="width:20px; height:20px; cursor:pointer;">
                                <label for="is_available" style="margin-left:0.5rem; cursor:pointer;">Available for Booking</label>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
                            <button type="button" id="cancelModal" class="btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Cancel</button>
                            <button type="submit" class="submitBtn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Save Table</button>
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
        let allRestaurants = [];

        function load(page = 1) {
            currentPage = page;
            fetch(api + '/admin_tables.php?page=' + page, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;

                    totalPages = data.pagination?.total_pages || 1;

                    // Update dropdown if empty
                    if (document.getElementById('restaurant_id').options.length <= 1 && data.restaurants) {
                        allRestaurants = data.restaurants;
                        const sel = document.getElementById('restaurant_id');
                        data.restaurants.forEach(r => {
                            const opt = document.createElement('option');
                            opt.value = r.id;
                            opt.textContent = r.name;
                            sel.appendChild(opt);
                        });
                    }

                    if (!data.tables || !data.tables.length) {
                        document.getElementById('tablesList').innerHTML = `
                            <div class="empty-state" style="padding: 2rem; text-align: center; border: 2px dashed var(--border-light); border-radius: var(--radius-lg);">
                                <i class="fa-solid fa-chair" style="font-size: 2rem; color: var(--border-medium); margin-bottom: 0.5rem;"></i>
                                <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">No tables found.</p>
                            </div>`;
                        return;
                    }

                    let html = '<table class="data-table" style="width:100%;"><thead><tr> <th>Table No.</th> <th>Restaurant</th> <th>Capacity</th> <th>Location</th> <th>Status</th> <th style="text-align:right;">Actions</th> </tr></thead><tbody>';

                    data.tables.forEach(t => {
                        const isAvail = t.is_available == 1;
                        const statusBadge = isAvail
                            ? `<span class="status-badge no-dot status-ok">Available</span>`
                            : `<span class="status-badge no-dot status-trash">Unavailable</span>`;

                        html += `<tr>
                            <td class="cell-primary">${escapeHtml(t.table_number)}</td>
                            <td class="cell-muted">${escapeHtml(t.restaurant_name)}</td>
                            <td><i class="fa-solid fa-user" style="color:var(--text-muted); font-size:0.8rem; margin-right:0.3rem;"></i>${t.capacity}</td>
                            <td style="text-transform:capitalize;">${escapeHtml(t.location)}</td>
                            <td>${statusBadge}</td>
                            <td style="text-align:right;">
                                <a href="javascript:void(0)" style="color:var(--primary-color); font-weight:600; font-size:0.85rem; text-decoration:none; cursor:pointer;" onclick='openEditModal(${JSON.stringify(t)})'>Edit</a>
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

                    document.getElementById('tablesList').innerHTML = html;
                });
        }

        function openEditModal(t) {
            document.getElementById('modalTitle').textContent = 'Edit Table';
            document.getElementById('table_id').value = t.id;
            document.getElementById('restaurant_id').value = t.restaurant_id;
            document.getElementById('table_number').value = t.table_number;
            document.getElementById('capacity').value = t.capacity;
            document.getElementById('location').value = t.location;
            document.getElementById('is_available').checked = t.is_available == 1;
            document.getElementById('tableModal').style.display = 'flex';
        }

        document.getElementById('addTableBtn').onclick = () => {
            document.getElementById('modalTitle').textContent = 'Add Table';
            document.getElementById('tableForm').reset();
            document.getElementById('table_id').value = '';
            document.getElementById('is_available').checked = true;
            document.getElementById('tableModal').style.display = 'flex';
        };

        const closeEls = [document.getElementById('closeModal'), document.getElementById('cancelModal')];
        closeEls.forEach(el => el.onclick = () => document.getElementById('tableModal').style.display = 'none');

        document.getElementById('tableForm').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'save');
            if (!document.getElementById('is_available').checked) {
                fd.append('is_available', 0);
            }

            fetch(api + '/admin_tables.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    document.getElementById('tableModal').style.display = 'none';
                    if (d.success) {
                        showMessageModal('success', 'Success', 'Table saved successfully.');
                        load(currentPage);
                    } else {
                        showMessageModal('error', 'Error', d.error || 'Could not save table.');
                    }
                })
                .catch(() => {
                     document.getElementById('tableModal').style.display = 'none';
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
