/**
 * Shared Pagination Utility — CEDULA project (AMORA-style)
 * Renders numbered pagination (1 2 3 … N) + a per-page limit selector.
 *
 * Usage:
 *   renderPagination(wrapperEl, currentPage, totalPages, perPage, onPageFn, onLimitFn);
 *
 * @param {HTMLElement} wrapper     - Container element for the pagination bar
 * @param {number}      current     - Active page (1-based)
 * @param {number}      total       - Total number of pages
 * @param {number}      perPage     - Current rows-per-page value
 * @param {Function}    onPage      - Callback(newPage) when a page button is clicked
 * @param {Function}    [onLimit]   - Callback(newLimit) when the per-page selector changes
 */
function renderPagination(wrapper, current, total, perPage, onPage, onLimit) {
    if (!wrapper) return;

    // --- Per-page selector ---
    const limitOptions = [5, 10, 15, 20, 30];
    let limitHtml = '';
    if (typeof onLimit === 'function') {
        limitHtml = `<div class="pg-limit-wrap">
            <label class="pg-label">Show</label>
            <select class="pg-limit-select" id="pgLimitSelect">
                ${limitOptions.map(n =>
                    `<option value="${n}"${n == perPage ? ' selected' : ''}>${n}</option>`
                ).join('')}
            </select>
            <label class="pg-label">entries</label>
        </div>`;
    }

    // --- Page number buttons with ellipsis ---
    function pageBtn(p, label, isActive, disabled) {
        const cls = ['pg-btn'];
        if (isActive)  cls.push('pg-active');
        if (disabled)  cls.push('pg-disabled');
        return `<button type="button" class="${cls.join(' ')}" data-page="${p}"${disabled ? ' disabled' : ''}>${label}</button>`;
    }

    function buildPages(cur, tot) {
        const pages = [];
        const WINDOW = 2; // pages visible around current

        // If total is 0 or 1, we still show the '1' button as active
        if (tot <= 1) {
            pages.push(pageBtn(1, '1', true, false));
            return `<div class="pg-pages">${pages.join('')}</div>`;
        }

        // Prev
        pages.push(pageBtn(cur - 1, '‹ Prev', false, cur <= 1));

        // Always show first page
        pages.push(pageBtn(1, '1', cur === 1, false));

        // Left ellipsis
        if (cur > WINDOW + 2) {
            pages.push(`<span class="pg-ellipsis">…</span>`);
        }

        // Window around current
        const start = Math.max(2, cur - WINDOW);
        const end   = Math.min(tot - 1, cur + WINDOW);
        for (let p = start; p <= end; p++) {
            pages.push(pageBtn(p, p, cur === p, false));
        }

        // Right ellipsis
        if (cur < tot - WINDOW - 1) {
            pages.push(`<span class="pg-ellipsis">…</span>`);
        }

        // Always show last page (if > 1)
        if (tot > 1) {
            pages.push(pageBtn(tot, tot, cur === tot, false));
        }

        // Next
        pages.push(pageBtn(cur + 1, 'Next ›', false, cur >= tot));

        return `<div class="pg-pages">${pages.join('')}</div>`;
    }

    wrapper.innerHTML = `
        <div class="pg-bar">
            ${limitHtml}
            ${buildPages(current, total)}
            <span class="pg-info">Page <strong>${current}</strong> of <strong>${total}</strong></span>
        </div>`;

    // Wire page buttons
    wrapper.querySelectorAll('.pg-btn:not(.pg-disabled)').forEach(btn => {
        btn.addEventListener('click', () => {
            const p = parseInt(btn.dataset.page, 10);
            if (p >= 1 && p <= total && p !== current) onPage(p);
        });
    });

    // Wire limit selector
    if (typeof onLimit === 'function') {
        const sel = wrapper.querySelector('#pgLimitSelect');
        if (sel) {
            sel.addEventListener('change', () => onLimit(parseInt(sel.value, 10)));
        }
    }
}

/* --- Styles injected once --- */
(function injectPgStyles() {
    if (document.getElementById('pg-utility-styles')) return;
    const s = document.createElement('style');
    s.id = 'pg-utility-styles';
    s.textContent = `
        .pg-bar { display:flex; align-items:center; flex-wrap:wrap; gap:.6rem; padding:.75rem 0; }
        .pg-limit-wrap { display:flex; align-items:center; gap:.4rem; }
        .pg-label { font-size:.8rem; color:var(--text-muted,#64748b); }
        .pg-limit-select {
            padding:.25rem .5rem; font-size:.8rem; border-radius:6px;
            border:1px solid var(--border-color,#e2e8f0);
            background:var(--bg-card,#fff); color:var(--text-heading,#111);
            cursor:pointer;
        }
        .pg-limit-select:focus { outline:none; border-color:var(--primary-color,#1a5653); }
        .pg-pages { display:flex; align-items:center; gap:3px; flex-wrap:wrap; }
        .pg-btn {
            padding:.3rem .65rem; font-size:.8rem; border-radius:6px; cursor:pointer;
            border:1px solid var(--border-color,#e2e8f0);
            background:var(--bg-card,#fff); color:var(--text-heading,#374151);
            transition:background .15s, color .15s;
            line-height:1.4;
        }
        .pg-btn:hover:not(.pg-disabled):not(.pg-active) {
            background:var(--primary-color,#1a5653); color:#fff; border-color:var(--primary-color,#1a5653);
        }
        .pg-active {
            background:var(--primary-color,#1a5653);
            color:#fff; border-color:var(--primary-color,#1a5653); font-weight:700; cursor:default;
        }
        .pg-disabled { opacity:.4; cursor:not-allowed; }
        .pg-ellipsis { padding:0 4px; color:var(--text-muted,#94a3b8); font-size:.85rem; user-select:none; }
        .pg-info { font-size:.8rem; color:var(--text-muted,#64748b); margin-left:auto; }
    `;
    document.head.appendChild(s);
})();
