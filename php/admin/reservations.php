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
    <title>Reservations - Admin</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'admin_reservations';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <h1 class="page-title">Manage Reservations</h1>
            <p class="page-subtitle">View and update reservation status.</p>

            <div id="reservationsList" class="table-container" style="margin-top: 1.5rem;"></div>

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
            fetch(api + '/admin_reservations.php?page=' + page, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;

                    totalPages = data.pagination?.total_pages || 1;

                    if (!data.reservations || !data.reservations.length) {
                        document.getElementById('reservationsList').innerHTML = `
                            <div class="empty-state" style="padding: 2rem; text-align: center; border: 2px dashed var(--border-light); border-radius: var(--radius-lg);">
                                <i class="fa-solid fa-calendar-xmark" style="font-size: 2rem; color: var(--border-medium); margin-bottom: 0.5rem;"></i>
                                <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">No reservations found.</p>
                            </div>`;
                        return;
                    }

                    let html = '<table class="data-table" style="width:100%;"><thead><tr> <th>ID</th> <th>Guest</th> <th>Restaurant</th> <th>Time</th> <th>Size</th> <th>Status</th> <th style="text-align:right;">Actions</th> </tr></thead><tbody>';

                    data.reservations.forEach(r => {
                        const statusMap = {
                            'pending': 'status-pending',
                            'confirmed': 'status-confirmed',
                            'completed': 'status-completed',
                            'cancelled': 'status-trash',
                            'no_show': 'status-no-show'
                        };
                        const sClass = statusMap[r.status] || 'status-pending';

                        // Select element for changing status
                        const statusSelect = `
                            <select onchange="updateStatus(${r.id}, this.value)" style="padding:0.25rem 0.5rem; border-radius:6px; border:1px solid var(--border-medium); font-size:0.8rem; cursor:pointer; background-color:var(--bg-input);">
                                <option value="pending" ${r.status==='pending'?'selected':''}>Pending</option>
                                <option value="confirmed" ${r.status==='confirmed'?'selected':''}>Confirmed</option>
                                <option value="completed" ${r.status==='completed'?'selected':''}>Completed</option>
                                <option value="cancelled" ${r.status==='cancelled'?'selected':''}>Cancelled</option>
                                <option value="no_show" ${r.status==='no_show'?'selected':''}>No Show</option>
                            </select>
                        `;

                        html += `<tr>
                            <td class="cell-mono">#${String(r.id).padStart(4, '0')}</td>
                            <td>
                                <div class="cell-primary">${escapeHtml(r.firstName + ' ' + r.lastName)}</div>
                                <div class="cell-muted">${escapeHtml(r.phone || r.email)}</div>
                            </td>
                            <td class="cell-muted">${escapeHtml(r.restaurant_name)}</td>
                            <td>
                                <div class="cell-primary">${new Date(r.reservation_date).toLocaleDateString(undefined, {month:'short', day:'numeric'})}</div>
                                <div class="cell-muted">${new Date('1970-01-01T' + r.reservation_time).toLocaleTimeString(undefined, {hour:'numeric', minute:'2-digit'})}</div>
                            </td>
                            <td><i class="fa-solid fa-user-group" style="color:var(--text-muted); font-size:0.75rem; margin-right:0.3rem;"></i>${r.party_size}</td>
                            <td><span class="status-badge no-dot ${sClass}">${escapeHtml(r.status.replace('_', ' '))}</span></td>
                            <td style="text-align:right;">
                                ${statusSelect}
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

                    document.getElementById('reservationsList').innerHTML = html;
                });
        }

        function updateStatus(id, newStatus) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('id', id);
            formData.append('status', newStatus);

            fetch(api + '/admin_reservations.php', { method: 'POST', body: formData, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        load(currentPage); // reload to update badge style
                        showMessageModal('success', 'Updated', 'Reservation status updated.');
                    } else {
                        showMessageModal('error', 'Error', d.error || 'Failed to update status.');
                    }
                })
                .catch(() => showMessageModal('error', 'Error', 'Network error.'));
        }

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
            setTimeout(() => { modal.style.display = 'none'; }, 2000); // auto close for quick actions
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        load();
    </script>
</body>
</html>
