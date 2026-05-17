<?php
/**
 * Unified Requests (Superadmin)
 * View all system requests (blocks, unblocks, and all approvals).
 */
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('superadmin');

$pageTitle = 'Manage Requests';
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
        .dashboard-main { padding: 2rem; max-width: 1200px; margin: 0 auto; width: 100%; }
        .data-table-container {
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow-x: auto;
            border: 1px solid var(--border-color);
            margin-top: 1.5rem;
            width: 100%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        .data-table th, .data-table td {
            padding: 1rem 1.25rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            word-wrap: break-word;
        }
        .data-table th {
            background: #f8fafc;
            font-weight: 700;
            font-size: 0.85rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .data-table tr:hover { background-color: #f1f5f9; transition: background 0.2s; }

        .search-filters {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .search-filters .form-group { flex: 1; min-width: 200px; margin-bottom: 0; }
        .search-filters .form-group label { display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 0.85rem; color: #64748b; }
        .search-filters input, .search-filters select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background-color: #f8fafc;
            transition: all 0.2s;
        }
        .search-filters input:focus, .search-filters select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 86, 83, 0.1);
            background-color: #fff;
        }
        .btn-reset {
            padding: 0.75rem 1.5rem;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-reset:hover { background: #e2e8f0; color: #1e293b; }

        .action-btns { display: flex; gap: 0.5rem; }
        .btn-action {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-approve { background: #dcfce7; color: #15803d; }
        .btn-approve:hover { background: #bbf7d0; }
        .btn-reject { background: #fee2e2; color: #b91c1c; }
        .btn-reject:hover { background: #fecaca; }

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

        @media (max-width: 1024px) {
            .data-table-container { overflow-x: auto; }
            .data-table { min-width: 900px; table-layout: auto; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <?php $currentPage = 'superadmin_requests'; include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1.5rem;">
                <div>
                    <h1 class="page-title" style="margin:0; font-size: 1.875rem; color: #0f172a;">Manage Requests</h1>
                    <p class="page-subtitle" style="margin: 0.5rem 0 0; color: #64748b;">System-wide activity overview and authorizations</p>
                </div>
                <div id="requestCountBadgeHeader" class="status-badge no-dot" style="background: #eff6ff; color: #2563eb; padding: 0.5rem 1.25rem; font-size: 0.875rem; font-weight: 700; border-radius: 9999px; border: 1px solid #dbeafe;">0 Requests</div>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <div class="form-group" style="flex: 2;">
                    <label><i class="fa-solid fa-magnifying-glass"></i> Keyword Search</label>
                    <input type="text" id="searchInput" placeholder="Search by name, username, or reason..." onkeyup="handleSearch(event)">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-calendar-day"></i> From</label>
                    <input type="date" id="startDate" onchange="applyFilters()">
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-calendar-day"></i> To</label>
                    <input type="date" id="endDate" onchange="applyFilters()">
                </div>
                <div class="form-group" style="max-width: 180px;">
                    <label><i class="fa-solid fa-filter"></i> Status</label>
                    <select id="filterStatus" onchange="applyFilters()">
                        <option value="">All History</option>
                        <option value="pending">Pending Only</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <button class="btn-reset" onclick="resetFilters()"><i class="fa-solid fa-rotate-right"></i> Reset</button>
            </div>

            <div id="requestsTableContainer" class="data-table-container">
                <div style="padding: 3rem; text-align: center; color: #94a3b8;">
                    <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
                    <p style="margin-top: 1rem;">Optimizing workspace...</p>
                </div>
            </div>

            <!-- Matched Pagination UI -->
            <div class="pagination-container">
                <span id="requestCountBadge" style="background:#dbeafe; color:#1e40af; padding:0.35rem 0.8rem; border-radius:20px; font-weight:700; font-size:0.85rem;">0 Requests</span>
                <div id="paginationControls" style="display: flex; align-items: center; justify-content: flex-end;"></div>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

    <script src="../../js/pagination_util.js"></script>
    <script>
        const api = '../../php/database';
        let currentPage = 1;
        let currentSearch = '';
        let currentStatus = '';
        let currentStartDate = '';
        let currentEndDate = '';
        let limit = 10;

        function loadRequests(page = 1) {
            currentPage = page;
            const params = new URLSearchParams({
                page: currentPage,
                limit: limit,
                search: currentSearch,
                status: currentStatus,
                startDate: currentStartDate,
                endDate: currentEndDate
            });

            fetch(api + '/superadmin_unified_requests.php?' + params.toString())
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('requestsTableContainer').innerHTML = `<div style="padding: 2rem; text-align: center; color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> ${data.error || 'Failed to load requests'}</div>`;
                        return;
                    }

                    let html = '<table class="data-table"><thead><tr>' +
                        '<th>Requester</th>' +
                        '<th>Target User</th>' +
                        '<th>Type</th>' +
                        '<th>Reason</th>' +
                        '<th>Date</th>' +
                        '<th>Status</th>' +
                        '<th>Action</th>' +
                        '</tr></thead><tbody>';

                    if (data.requests.length === 0) {
                        html += '<tr><td colspan="7" style="text-align:center; padding:4rem; color: #94a3b8;"><i class="fa-regular fa-folder-open fa-3x" style="display:block; margin-bottom:1rem; opacity:0.5;"></i> No matching requests found.</td></tr>';
                    } else {
                        data.requests.forEach(r => {
                            let statusClass = 'status-pending';
                            if (r.status === 'approved') statusClass = 'status-ok';
                            if (r.status === 'rejected') statusClass = 'status-trash';

                            const typeLabel = r.request_type.toUpperCase();
                            const typeStyle = r.request_type === 'registration' ? 'background: #dbeafe; color: #1e40af;' :
                                              r.request_type === 'unblock' ? 'background: #dcfce7; color: #166534;' :
                                              'background: #fee2e2; color: #991b1b;';

                            html += `<tr>
                                <td><div style="font-weight:700; color: #334155;">${escapeHtml(r.requester_first + ' ' + r.requester_last)}</div></td>
                                <td>
                                    <div style="font-weight:700; color: #334155;">${escapeHtml(r.target_first + ' ' + r.target_last)}</div>
                                    <div class="muted small" style="color: #64748b;">@${escapeHtml(r.target_username)}</div>
                                </td>
                                <td><span class="status-badge no-dot" style="font-size: 0.7rem; font-weight: 800; border-radius: 6px; ${typeStyle}">${typeLabel}</span></td>
                                <td style="font-size: 0.875rem; line-height: 1.4; color: #475569;">${escapeHtml(r.reason)}</td>
                                <td style="font-size: 0.875rem; color: #475569; font-weight: 500;">${new Date(r.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                                <td><span class="status-badge no-dot ${statusClass}" style="font-weight:700;">${r.status.toUpperCase()}</span></td>
                                <td>
                                    ${r.status === 'pending' ? `
                                        <div class="action-btns">
                                            <button class="btn-action btn-approve" title="Approve" onclick="handleRequest(${r.request_id}, '${r.source_table}', 'approve')"><i class="fa-solid fa-check"></i></button>
                                            <button class="btn-action btn-reject" title="Reject" onclick="handleRequest(${r.request_id}, '${r.source_table}', 'reject')"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                    ` : '<span style="color: #94a3b8; font-size: 0.75rem; font-weight: 600;">DONE</span>'}
                                </td>
                            </tr>`;
                        });
                    }
                    html += '</tbody></table>';
                    document.getElementById('requestsTableContainer').innerHTML = html;
                    updatePagination(data.pagination);
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                });
        }

        function updatePagination(p) {
            const controls = document.getElementById('paginationControls');
            const badgeHeader = document.getElementById('requestCountBadgeHeader');
            const badgeFooter = document.getElementById('requestCountBadge');
            
            const countText = `${p.total_requests} Request${p.total_requests !== 1 ? 's' : ''}`;
            if (badgeHeader) badgeHeader.textContent = countText;
            if (badgeFooter) badgeFooter.textContent = countText;
            
            window.renderPagination(controls, currentPage, p.total_pages || 1, limit, n => loadRequests(n), l => { limit = l; loadRequests(1); });
        }

        let searchTimeout;
        function handleSearch(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { currentSearch = e.target.value; loadRequests(1); }, 300);
        }

        function applyFilters() {
            currentStatus = document.getElementById('filterStatus').value;
            currentStartDate = document.getElementById('startDate').value;
            currentEndDate = document.getElementById('endDate').value;
            loadRequests(1);
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            currentSearch = ''; currentStatus = ''; currentStartDate = ''; currentEndDate = '';
            loadRequests(1);
        }

        function handleRequest(id, table, action) {
            const verb = action === 'approve' ? 'APPROVE' : 'REJECT';
            if (!confirm(`Are you sure you want to ${verb} this request? This action cannot be undone.`)) return;
            const fd = new FormData(); fd.append('request_id', id); fd.append('source_table', table); fd.append('action', action);
            document.getElementById('requestsTableContainer').style.opacity = '0.5';
            fetch(api + '/unified_request_action.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(d => {
                    if (d.success) {
                        if (d.superadmin_swap) {
                            alert("CRITICAL SECURITY ACTION: You have approved the unblocking of another Superadmin. As the system only allows one active Superadmin, your account has been blocked and you will now be logged out. Please log in with the other Superadmin's credentials.");
                            window.location.href = '../auth/logout.php';
                            return;
                        }
                        loadRequests(currentPage);
                    }
                    else { alert(d.error); document.getElementById('requestsTableContainer').style.opacity = '1'; }
                })
                .catch(err => { alert('Network error.'); document.getElementById('requestsTableContainer').style.opacity = '1'; });
        }

        function escapeHtml(s) { if(!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
        loadRequests();
    </script>
</body>
</html>
