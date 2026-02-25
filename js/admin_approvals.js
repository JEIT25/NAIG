/**
 * Admin - My Approval Requests (AMORA-style, rebuilt)
 * Shows ONLY this admin's own approval requests (context=my).
 */

document.addEventListener('DOMContentLoaded', function () {
    const API_BASE = (window.API_BASE || '');
    const container = document.getElementById('approvals-container');
    const paginationEl = document.getElementById('pagination');
    const statusFilters = document.querySelectorAll('.status-filter');
    const searchInput = document.getElementById('searchInput');

    let currentPage = 1;
    let currentStatus = 'pending';
    let currentSearch = '';
    let searchTimeout = null;

    function escapeHtml(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function formatDate(s) {
        if (!s) return '—';
        const d = new Date(s);
        return isNaN(d.getTime()) ? s : d.toLocaleString();
    }

    function actionLabel(actionType) {
        if (actionType === 'delete_user') return 'Delete user';
        if (actionType === 'delete_restaurant') return 'Delete restaurant';
        if (actionType === 'delete_menu_item') return 'Delete menu item';
        if (actionType === 'register_consumer') return 'New consumer registration';
        return actionType || 'Request';
    }

    function loadApprovals(page, status) {
        if (!container) return;
        container.innerHTML =
            '<div class="loading-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading requests...</p></div>';

        // "context=my" ensures admins only see their own created approval rows
        const params = new URLSearchParams({
            context: 'my',
            status: status,
            page: String(page),
            per_page: '10'
        });
        if (currentSearch) params.append('search', currentSearch);

        fetch(API_BASE + 'approval_list.php?' + params.toString(), { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    container.innerHTML =
                        '<div class="empty-state"><p>' +
                        escapeHtml(data.error || 'Failed to load requests.') +
                        '</p></div>';
                    return;
                }
                displayApprovals(data.approvals || []);
                displayPagination(data.pagination);
            })
            .catch(() => {
                container.innerHTML =
                    '<div class="empty-state"><p>Network error while loading requests.</p></div>';
            });
    }

    function displayApprovals(approvals) {
        if (!approvals || approvals.length === 0) {
            container.innerHTML =
                '<div class="empty-state"><p>No requests found.</p></div>';
            return;
        }

        const cards = approvals
            .map(a => {
                const status = a.status || 'pending';
                const statusClass =
                    status === 'approved'
                        ? 'approved'
                        : status === 'rejected'
                        ? 'rejected'
                        : 'pending';

                const reviewer =
                    a.reviewer_firstName && a.reviewer_lastName
                        ? a.reviewer_firstName + ' ' + a.reviewer_lastName
                        : null;

                let reviewBlock;
                if (status === 'pending') {
                    reviewBlock =
                        '<div class="approval-card-meta" style="margin-top:8px;"><em>Awaiting review...</em></div>';
                } else {
                    reviewBlock =
                        '<div class="approval-card-meta" style="margin-top:8px;padding-top:8px;border-top:1px solid #eee;">' +
                        '<strong>Reviewed by:</strong> ' +
                        escapeHtml(reviewer || '—') +
                        '<br><strong>At:</strong> ' +
                        formatDate(a.reviewed_at) +
                        (a.review_notes
                            ? '<br><strong>Notes:</strong> ' + escapeHtml(a.review_notes)
                            : '') +
                        '</div>';
                }

                return (
                    '<div class="approval-card">' +
                    '<div class="approval-card-title">' +
                    escapeHtml(actionLabel(a.action_type)) +
                    ' (target: ' +
                    escapeHtml(a.target_type || '') +
                    ' #' +
                    escapeHtml(a.target_id || '') +
                    ')</div>' +
                    '<span class="approval-status ' +
                    statusClass +
                    '">' +
                    escapeHtml(status) +
                    '</span>' +
                    '<div class="approval-card-meta">Submitted: ' +
                    formatDate(a.created_at) +
                    '</div>' +
                    (a.reason
                        ? '<p><strong>Reason:</strong> ' +
                          escapeHtml(a.reason) +
                          '</p>'
                        : '') +
                    reviewBlock +
                    '</div>'
                );
            })
            .join('');

        container.innerHTML = cards;
    }

    function displayPagination(pagination) {
        if (!paginationEl || !pagination) return;
        const totalPages = pagination.total_pages || 1;
        let html = '';
        if (currentPage > 1) {
            html += '<button type="button" data-page="' + (currentPage - 1) + '">Prev</button>';
        }
        html +=
            ' <span>Page ' +
            currentPage +
            ' of ' +
            totalPages +
            '</span> ';
        if (currentPage < totalPages) {
            html += '<button type="button" data-page="' + (currentPage + 1) + '">Next</button>';
        }
        paginationEl.innerHTML = html;
        paginationEl.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                const page = parseInt(btn.dataset.page, 10);
                if (!isNaN(page)) {
                    currentPage = page;
                    loadApprovals(currentPage, currentStatus);
                }
            });
        });
    }

    // Status filters
    statusFilters.forEach(btn => {
        if (btn.dataset.status === currentStatus) btn.classList.add('active');
        btn.addEventListener('click', () => {
            statusFilters.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentStatus = btn.dataset.status;
            currentPage = 1;
            loadApprovals(currentPage, currentStatus);
        });
    });

    // Search
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = searchInput.value.trim();
                currentPage = 1;
                loadApprovals(currentPage, currentStatus);
            }, 400);
        });
    }

    // Initial load
    loadApprovals(currentPage, currentStatus);
});

/**
 * Admin - My Approval Requests (AMORA-style)
 */
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE = typeof API_BASE !== 'undefined' ? API_BASE : '';
    const container = document.getElementById('approvals-container');
    const paginationEl = document.getElementById('pagination');
    const statusFilters = document.querySelectorAll('.status-filter');
    const searchInput = document.getElementById('searchInput');
    let currentPage = 1;
    let currentStatus = 'pending';
    let currentSearch = '';
    let searchTimeout = null;

    function escapeHtml(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function formatDate(s) {
        if (!s) return '—';
        const d = new Date(s);
        return isNaN(d.getTime()) ? s : d.toLocaleString();
    }

    function loadApprovals(page, status) {
        if (!container) return;
        container.innerHTML = '<div class="loading-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading...</p></div>';
        if (status === 'all') {
            Promise.all([
                fetch(API_BASE + 'approval_list.php?context=my&status=pending&per_page=50' + (currentSearch ? '&search=' + encodeURIComponent(currentSearch) : ''), { credentials: 'same-origin' }).then(r => r.json()),
                fetch(API_BASE + 'approval_list.php?context=my&status=approved&per_page=50' + (currentSearch ? '&search=' + encodeURIComponent(currentSearch) : ''), { credentials: 'same-origin' }).then(r => r.json()),
                fetch(API_BASE + 'approval_list.php?context=my&status=rejected&per_page=50' + (currentSearch ? '&search=' + encodeURIComponent(currentSearch) : ''), { credentials: 'same-origin' }).then(r => r.json())
            ]).then(function(arr) {
                let all = [];
                arr.forEach(function(data) { if (data.success && data.approvals) all = all.concat(data.approvals); });
                all.sort(function(a, b) { return new Date(b.created_at) - new Date(a.created_at); });
                displayApprovals(all);
                paginationEl.innerHTML = '';
            }).catch(function() {
                container.innerHTML = '<div class="empty-state"><p>Error loading requests.</p></div>';
            });
            return;
        }
        let url = API_BASE + 'approval_list.php?context=my&page=' + page + '&status=' + encodeURIComponent(status) + '&per_page=10';
        if (currentSearch) url += '&search=' + encodeURIComponent(currentSearch);
        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    displayApprovals(data.approvals);
                    displayPagination(data.pagination);
                } else {
                    container.innerHTML = '<div class="empty-state"><p>' + escapeHtml(data.error || 'Failed') + '</p></div>';
                }
            })
            .catch(function() {
                container.innerHTML = '<div class="empty-state"><p>Network error.</p></div>';
            });
    }

    function actionLabel(actionType) {
        if (actionType === 'delete_user') return 'Delete user';
        if (actionType === 'delete_restaurant') return 'Delete restaurant';
        if (actionType === 'delete_menu_item') return 'Delete menu item';
        return actionType || 'Request';
    }

    function displayApprovals(approvals) {
        if (!approvals || approvals.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>No requests found.</p></div>';
            return;
        }
        container.innerHTML = approvals.map(function(a) {
            var reviewer = (a.reviewer_firstName && a.reviewer_lastName) ? (a.reviewer_firstName + ' ' + a.reviewer_lastName) : null;
            var reviewBlock = a.status !== 'pending' ? (
                '<div class="approval-card-meta" style="margin-top:8px;padding-top:8px;border-top:1px solid #eee;">' +
                '<strong>Reviewed by:</strong> ' + escapeHtml(reviewer || '—') + '<br>' +
                '<strong>At:</strong> ' + formatDate(a.reviewed_at) + '<br>' +
                (a.review_notes ? '<strong>Notes:</strong> ' + escapeHtml(a.review_notes) : '') +
                '</div>'
            ) : '<div class="approval-card-meta" style="margin-top:8px;"><em>Awaiting superadmin review...</em></div>';
            return '<div class="approval-card">' +
                '<div class="approval-card-title">' + actionLabel(a.action_type) + ' (target: ' + escapeHtml(a.target_type) + ' #' + escapeHtml(a.target_id) + ')</div>' +
                '<span class="approval-status ' + a.status + '">' + a.status + '</span>' +
                '<div class="approval-card-meta">Submitted: ' + formatDate(a.created_at) + '</div>' +
                (a.reason ? '<p><strong>Reason:</strong> ' + escapeHtml(a.reason) + '</p>' : '') +
                reviewBlock +
                '</div>';
        }).join('');
    }

    function displayPagination(p) {
        if (!paginationEl || !p) return;
        var totalPages = p.total_pages || 1;
        var html = '';
        if (currentPage > 1) html += '<button type="button" data-page="' + (currentPage - 1) + '">Prev</button>';
        html += ' <span>Page ' + currentPage + ' of ' + totalPages + '</span> ';
        if (currentPage < totalPages) html += '<button type="button" data-page="' + (currentPage + 1) + '">Next</button>';
        paginationEl.innerHTML = html;
        paginationEl.querySelectorAll('button').forEach(function(b) {
            b.addEventListener('click', function() {
                currentPage = parseInt(this.dataset.page, 10);
                loadApprovals(currentPage, currentStatus);
            });
        });
    }

    statusFilters.forEach(function(btn) {
        if (btn.dataset.status === currentStatus) btn.classList.add('active');
        btn.addEventListener('click', function() {
            statusFilters.forEach(function(f) { f.classList.remove('active'); });
            this.classList.add('active');
            currentStatus = this.dataset.status;
            currentPage = 1;
            loadApprovals(currentPage, currentStatus);
        });
    });
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                currentSearch = searchInput.value.trim();
                currentPage = 1;
                loadApprovals(currentPage, currentStatus);
            }, 400);
        });
    }
    loadApprovals(1, currentStatus);
});
