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

        /* Column Widths (Approximations with table-layout: auto) */
        .col-requester { width: 15%; min-width: 150px; }
        .col-target { width: 15%; min-width: 150px; }
        .col-type { width: 10%; min-width: 100px; }
        .col-reason { width: auto; min-width: 200px; }
        .col-date { width: 12%; min-width: 120px; }
        .col-status { width: 10%; min-width: 100px; }
        .col-action { width: 12%; min-width: 140px; }

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
        .search-filters .form-group { flex: 1; min-width: 250px; margin-bottom: 0; }
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
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
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

        @media (max-width: 1024px) {
            .data-table-container { overflow-x: auto; }
            .data-table { min-width: 900px; table-layout: auto; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <?php $currentPage = 'superadmin_requests';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1.5rem;">
                <div>
                    <h1 class="page-title" style="margin:0; font-size: 1.875rem; color: #0f172a;">Manage Requests</h1>
                    <p class="page-subtitle" style="margin: 0.5rem 0 0; color: #64748b;">System-wide activity overview and authorizations</p>
                </div>
                <div id="requestCountBadge" class="status-badge no-dot" style="background: #eff6ff; color: #2563eb; padding: 0.5rem 1.25rem; font-size: 0.875rem; font-weight: 700; border-radius: 9999px; border: 1px solid #dbeafe;">0 Requests</div>
            </div>

            <!-- Search and Filters -->
            <div class="search-filters">
                <div class="form-group">
                    <label><i class="fa-solid fa-magnifying-glass"></i> Search Filter</label>
                    <input type="text" id="searchInput" placeholder="Search by name, username, or reason..." onkeyup="handleSearch(event)">
                </div>
                <div class="form-group" style="max-width: 200px;">
                    <label><i class="fa-solid fa-filter"></i> Status</label>
                    <select id="filterStatus" onchange="applyFilters()">
                        <option value="">All History</option>
                        <option value="pending" selected>Pending Only</option>
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

            <div id="paginationContainer" style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 1rem 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div id="paginationInfo" style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Showing 0-0 of 0 requests</div>
                <div id="paginationControls" style="display: flex; gap: 0.5rem; align-items: center;">
                </div>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>

    <script>
        const api = '../../php/database';
        let currentPage = 1;
        let currentSearch = '';
        let currentStatus = 'pending';

        function loadRequests(page = 1) {
            currentPage = page;
            const params = new URLSearchParams({
                page: currentPage,
                limit: 10,
                search: currentSearch,
                status: currentStatus
            });

            fetch(api + '/superadmin_unified_requests.php?' + params.toString())
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('requestsTableContainer').innerHTML = `<div style="padding: 2rem; text-align: center; color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> ${data.error || 'Failed to load requests'}</div>`;
                        return;
                    }

                    let html = '<table class="data-table"><thead><tr>' +
                        '<th class="col-requester">Requester</th>' +
                        '<th class="col-target">Target User</th>' +
                        '<th class="col-type">Type</th>' +
                        '<th class="col-reason">Reason</th>' +
                        '<th class="col-date">Date</th>' +
                        '<th class="col-status">Status</th>' +
                        '<th class="col-action">Action</th>' +
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
                                <td>
                                    <div style="font-weight:700; color: #334155;">${escapeHtml(r.requester_first + ' ' + r.requester_last)}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700; color: #334155;">${escapeHtml(r.target_first + ' ' + r.target_last)}</div>
                                    <div class="muted small" style="color: #64748b;">@${escapeHtml(r.target_username)}</div>
                                </td>
                                <td>
                                    <span class="status-badge no-dot" style="font-size: 0.7rem; font-weight: 800; border-radius: 6px; ${typeStyle}">
                                        ${typeLabel}
                                    </span>
                                </td>
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
                    renderPagination(data.pagination);

                    // Reset UI state
                    const container = document.getElementById('requestsTableContainer');
                    if (container) {
                        container.style.opacity = '1';
                        container.style.pointerEvents = 'auto';
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    const container = document.getElementById('requestsTableContainer');
                    if (container) {
                        container.style.opacity = '1';
                        container.style.pointerEvents = 'auto';
                    }
                });
        }

        function renderPagination(p) {
            const info = document.getElementById('paginationInfo');
            const controls = document.getElementById('paginationControls');
            const badge = document.getElementById('requestCountBadge');

            badge.textContent = `${p.total_requests} Request${p.total_requests !== 1 ? 's' : ''}`;
            const start = (p.current_page - 1) * p.limit + 1;
            const end = Math.min(start + p.limit - 1, p.total_requests);
            info.textContent = `Showing ${p.total_requests > 0 ? start : 0}-${end} of ${p.total_requests}`;

            let html = '';
            html += `<button class="btn-reset" style="padding: 0.4rem 0.8rem;" ${p.current_page === 1 ? 'disabled' : `onclick="loadRequests(${p.current_page - 1})"`}><i class="fa-solid fa-chevron-left"></i></button>`;
            for (let i = 1; i <= p.total_pages; i++) {
                if (i === 1 || i === p.total_pages || (i >= p.current_page - 1 && i <= p.current_page + 1)) {
                    const isActive = i === p.current_page;
                    html += `<button class="${isActive ? 'btn-primary' : 'btn-reset'}" style="padding: 0.4rem 0.8rem; min-width: 2.5rem; ${isActive ? 'background:var(--primary-color); color:white; border-color:var(--primary-color);' : ''}" onclick="loadRequests(${i})">${i}</button>`;
                } else if (i === p.current_page - 2 || i === p.current_page + 2) {
                    html += `<span style="padding: 0 0.25rem; color: #94a3b8;">...</span>`;
                }
            }
            html += `<button class="btn-reset" style="padding: 0.4rem 0.8rem;" ${p.current_page === p.total_pages || p.total_pages === 0 ? 'disabled' : `onclick="loadRequests(${p.current_page + 1})"`}><i class="fa-solid fa-chevron-right"></i></button>`;
            controls.innerHTML = html;
        }

        let searchTimeout;
        function handleSearch(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = e.target.value;
                loadRequests(1);
            }, 300);
        }

        function applyFilters() {
            currentStatus = document.getElementById('filterStatus').value;
            loadRequests(1);
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').value = 'pending';
            currentSearch = '';
            currentStatus = 'pending';
            loadRequests(1);
        }

        function handleRequest(id, table, action) {
            const verb = action === 'approve' ? 'APPROVE' : 'REJECT';
            if (!confirm(`Are you sure you want to ${verb} this request? This action cannot be undone.`)) return;

            const fd = new FormData();
            fd.append('request_id', id);
            fd.append('source_table', table);
            fd.append('action', action);

            // Visual feedback
            document.getElementById('requestsTableContainer').style.opacity = '0.5';
            document.getElementById('requestsTableContainer').style.pointerEvents = 'none';

            fetch(api + '/unified_request_action.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) loadRequests(currentPage);
                    else {
                        alert(d.error);
                        document.getElementById('requestsTableContainer').style.opacity = '1';
                        document.getElementById('requestsTableContainer').style.pointerEvents = 'auto';
                    }
                })
                .catch(err => {
                    alert('Network error. Please try again.');
                    document.getElementById('requestsTableContainer').style.opacity = '1';
                    document.getElementById('requestsTableContainer').style.pointerEvents = 'auto';
                });
        }

        function escapeHtml(s) { if(!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
        loadRequests();
    </script>
</body>
</html>

