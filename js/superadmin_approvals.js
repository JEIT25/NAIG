/**
 * Superadmin/Admin - Approval Requests (AMORA-style, rebuilt)
 * - Superadmin: can review all approvals (delete, register_consumer, etc.).
 * - Admin: can review only registration approvals (enforced server-side).
 */

document.addEventListener('DOMContentLoaded', function () {
    const API_BASE = (window.API_BASE || '');
    const container = document.getElementById('approvals-container');
    const paginationEl = document.getElementById('pagination');
    const statusFilters = document.querySelectorAll('.status-filter');
    const searchInput = document.getElementById('searchInput');

    const reviewModal = document.getElementById('reviewApprovalModal');
    const reviewTitle = document.getElementById('reviewApprovalTitle');
    const reviewMessage = document.getElementById('reviewApprovalMessage');
    const reviewNotes = document.getElementById('reviewApprovalNotes');
    const reviewCancelBtn = document.getElementById('reviewApprovalCancel');
    const reviewApproveBtn = document.getElementById('reviewApprovalApprove');
    const reviewRejectBtn = document.getElementById('reviewApprovalReject');

    let currentPage = 1;
    let currentStatus = 'pending';
    let currentSearch = '';
    let searchTimeout = null;
    let currentApprovalId = null;
    let currentAction = null; // 'approve' or 'reject'

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
            '<div class="loading-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading...</p></div>';

        const params = new URLSearchParams({
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

        let html = '<table class="data-table"><thead><tr>' +
            '<th>Requester</th>' +
            '<th>Action</th>' +
            '<th>Target</th>' +
            '<th>Status</th>' +
            '<th>Submitted</th>' +
            '<th>Reviewed By</th>' +
            '<th>Reason</th>' +
            '<th>Action</th>' +
            '</tr></thead><tbody>';

        approvals.forEach(a => {
            const status = a.status || 'pending';
            const requester =
                a.requester_firstName && a.requester_lastName
                    ? a.requester_firstName + ' ' + a.requester_lastName
                    : (a.requested_by || '');
            const reviewer =
                a.reviewer_firstName && a.reviewer_lastName
                    ? a.reviewer_firstName + ' ' + a.reviewer_lastName
                    : '';

            let statusBadgeClass = 'status-pending';
            if (status === 'approved') statusBadgeClass = 'status-ok';
            if (status === 'rejected') statusBadgeClass = 'status-trash';

            let actionButtons = '';
            if (status === 'pending') {
                actionButtons =
                    '<button type="button" class="btn-secondary review-approve" data-id="' + a.id + '">Approve</button> ' +
                    '<button type="button" class="btn-secondary review-reject" data-id="' + a.id + '">Reject</button>';
            } else {
                actionButtons = '<span class="muted small">No actions</span>';
            }

            html += '<tr data-id="' + a.id + '">' +
                '<td>' + escapeHtml(requester) + '</td>' +
                '<td>' + escapeHtml(actionLabel(a.action_type)) + '</td>' +
                '<td>' + escapeHtml((a.target_type || '') + ' #' + (a.target_id || '')) + '</td>' +
                '<td><span class="status-badge no-dot ' + statusBadgeClass + '">' + escapeHtml(status) + '</span></td>' +
                '<td>' + formatDate(a.created_at) + '</td>' +
                '<td>' + escapeHtml(reviewer) + '</td>' +
                '<td>' + escapeHtml(a.reason || '') + '</td>' +
                '<td>' + actionButtons + '</td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;

        // Wire action buttons
        container.querySelectorAll('.review-approve').forEach(btn => {
            btn.addEventListener('click', () => openReviewModal(btn.dataset.id, 'approve'));
        });
        container.querySelectorAll('.review-reject').forEach(btn => {
            btn.addEventListener('click', () => openReviewModal(btn.dataset.id, 'reject'));
        });
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

    function openReviewModal(approvalId, action) {
        currentApprovalId = parseInt(approvalId, 10);
        currentAction = action;
        if (!reviewModal) return;

        if (reviewTitle)
            reviewTitle.textContent =
                action === 'approve' ? 'Approve request' : 'Reject request';
        if (reviewMessage)
            reviewMessage.textContent =
                action === 'approve'
                    ? 'Add optional notes and confirm approval.'
                    : 'Add optional notes and confirm rejection.';
        if (reviewNotes) reviewNotes.value = '';

        reviewModal.classList.add('show');
    }

    function closeReviewModal() {
        if (reviewModal) reviewModal.classList.remove('show');
        currentApprovalId = null;
        currentAction = null;
    }

    function submitReview(action) {
        if (!currentApprovalId || !action) return;
        const fd = new FormData();
        fd.append('approval_id', String(currentApprovalId));
        fd.append('action', action);
        fd.append('review_notes', reviewNotes ? reviewNotes.value.trim() : '');

        fetch(API_BASE + 'approval_review.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
            .then(r => r.json())
            .then(data => {
                closeReviewModal();
                showNotification(
                    data.success ? 'Success' : 'Error',
                    data.message || data.error || (data.success ? 'Done.' : 'Failed.')
                );
                if (data.success) {
                    loadApprovals(currentPage, currentStatus);
                }
            })
            .catch(() => {
                showNotification('Error', 'Network error.');
            });
    }

    function showNotification(title, message) {
        const modal = document.getElementById('notificationModal');
        const titleEl = document.getElementById('notificationTitle');
        const msgEl = document.getElementById('notificationMessage');
        const footerBtn = document.getElementById('notificationFooterBtn');
        if (!modal || !footerBtn) return;

        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;
        modal.classList.add('show');
        modal.classList.toggle('error', title === 'Error');
        modal.classList.toggle('success', title === 'Success');

        const close = () => {
            modal.classList.remove('show');
            footerBtn.removeEventListener('click', close);
        };
        footerBtn.addEventListener('click', close);
    }

    // Wire modal buttons
    if (reviewCancelBtn) {
        reviewCancelBtn.addEventListener('click', closeReviewModal);
    }
    if (reviewApproveBtn) {
        reviewApproveBtn.addEventListener('click', () => submitReview('approve'));
    }
    if (reviewRejectBtn) {
        reviewRejectBtn.addEventListener('click', () => submitReview('reject'));
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

