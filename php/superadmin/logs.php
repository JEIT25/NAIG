<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('superadmin');
$pageTitle = 'Login Logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NAIGO</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .filters { display: flex; gap: 1rem; margin-bottom: 1rem; align-items: center; background: var(--bg-card); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); }
        .logs-table { width: 100%; border-collapse: collapse; background: var(--bg-card); border-radius: 8px; overflow: hidden; }
        .logs-table th, .logs-table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .logs-table th { background: var(--bg-body); font-weight: 600; }
        
        /* Premium Pagination Container Styles (Matched to Image) */
        .pagination-container {
            margin-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'superadmin_logs'; include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <header style="margin-bottom: 2rem;">
                <h1 class="page-title" style="font-size: 1.75rem; margin-bottom: 0.25rem;">Login Logs</h1>
                <p class="page-subtitle text-muted" style="margin: 0; font-size: 0.95rem;">Audit system access and user session durations.</p>
            </header>

            <div class="filters" style="flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <label style="display:block; font-size:0.8rem; font-weight:600; color:#64748b; margin-bottom:0.5rem;">Search</label>
                    <input type="text" id="filterSearch" class="input-field" placeholder="Search by name or username" onkeyup="debounceLoadLogs()">
                </div>

                <div style="width: 150px;">
                    <label style="display:block; font-size:0.8rem; font-weight:600; color:#64748b; margin-bottom:0.5rem;">Role</label>
                    <select id="filterRole" class="input-field" onchange="loadLogs(1)">
                        <option value="">All Roles</option>
                        <option value="superadmin">Superadmin</option>
                        <option value="admin">Admin</option>
                        <option value="consumer">Consumer</option>
                    </select>
                </div>

                <div style="width: auto;">
                    <label style="display:block; font-size:0.8rem; font-weight:600; color:#64748b; margin-bottom:0.5rem;">Date</label>
                    <input type="date" id="filterDate" class="input-field" onchange="loadLogs(1)">
                </div>

                <div style="padding-top: 1.5rem;">
                    <button class="btn-secondary" onclick="resetFilters()" style="padding: 0.75rem 1.5rem;">Reset</button>
                </div>
            </div>

            <div id="logsTableContainer">Loading...</div>

            <!-- Matched Pagination UI -->
            <div class="pagination-container">
                <span id="logCountBadge" style="background:#dbeafe; color:#1e40af; padding:0.35rem 0.8rem; border-radius:20px; font-weight:700; font-size:0.85rem;">0 Logs</span>
                <div id="paginationControls" style="display: flex; align-items: center; justify-content: flex-end;"></div>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

    <script src="../../js/pagination_util.js"></script>
    <script>
        const api = '../../php/database';
        let currentPage = 1;
        let limit = 10;
        let debounceTimer;

        function debounceLoadLogs() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadLogs(1), 300);
        }

        function loadLogs(page = 1) {
            currentPage = page;
            const date = document.getElementById('filterDate').value;
            const search = document.getElementById('filterSearch').value;
            const role = document.getElementById('filterRole').value;

            const params = new URLSearchParams({ date, search, role, page, limit });

            document.getElementById('logsTableContainer').innerHTML = '<div style="padding:3rem; text-align:center; color:#94a3b8;"><i class="fa-solid fa-circle-notch fa-spin fa-2x"></i><p style="margin-top:1rem;">Fetching audit logs...</p></div>';

            fetch(api + '/superadmin_logs_list.php?' + params)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('logsTableContainer').innerHTML = '<div style="padding:2rem; text-align:center; color:#ef4444;">Failed to load logs.</div>';
                        return;
                    }

                    if (data.logs.length === 0) {
                        document.getElementById('logsTableContainer').innerHTML = '<div style="padding:4rem; text-align:center; color:#94a3b8;"><i class="fa-regular fa-folder-open fa-3x" style="display:block; margin-bottom:1rem; opacity:0.5;"></i>No logs found matching your criteria.</div>';
                        updatePagination(data.pagination);
                        return;
                    }

                    let html = '<table class="data-table"><thead><tr><th>User</th><th>Role</th><th>Login Time</th><th>Logout Time</th><th>Duration</th></tr></thead><tbody>';
                    data.logs.forEach(l => {
                        const loginDate = new Date(l.login_time);
                        const logoutDate = l.logout_time ? new Date(l.logout_time) : null;

                        const fmtLogin = loginDate.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                        const fmtLogout = logoutDate ? logoutDate.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '<span class="muted">--</span>';

                        let duration = '-';
                        if (logoutDate) {
                            const diffMs = logoutDate - loginDate;
                            const diffMins = Math.floor(diffMs / 60000);
                            const hrs = Math.floor(diffMins / 60);
                            const mins = diffMins % 60;
                            duration = hrs > 0 ? `${hrs}h ${mins}m` : `${mins}m`;
                        }

                        let roleClass = 'status-pending';
                        if (l.role === 'admin') roleClass = 'status-ok';
                        if (l.role === 'superadmin') roleClass = 'status-trash';

                        html += `<tr>
                            <td>
                                <div style="font-weight:700; color:#334155;">${escapeHtml(l.firstName + ' ' + l.lastName)}</div>
                                <div style="font-size:0.75rem; color:#64748b;">@${escapeHtml(l.username)}</div>
                            </td>
                            <td><span class="status-badge no-dot ${roleClass}">${l.role}</span></td>
                            <td style="font-size:0.85rem;">${fmtLogin}</td>
                            <td style="font-size:0.85rem;">${fmtLogout}</td>
                            <td style="font-weight:600; color:#475569;">${duration}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('logsTableContainer').innerHTML = html;
                    updatePagination(data.pagination);
                });
        }

        function updatePagination(p) {
            const controls = document.getElementById('paginationControls');
            const badge = document.getElementById('logCountBadge');
            badge.textContent = `${p.total_records} Log${p.total_records !== 1 ? 's' : ''}`;
            window.renderPagination(controls, currentPage, p.total_pages || 1, limit, n => loadLogs(n), l => { limit = l; loadLogs(1); });
        }

        function resetFilters() {
            document.getElementById('filterSearch').value = '';
            document.getElementById('filterRole').value = '';
            document.getElementById('filterDate').value = '';
            loadLogs(1);
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        loadLogs();
    </script>
</body>
</html>
