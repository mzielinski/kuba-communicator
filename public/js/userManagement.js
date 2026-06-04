// ============================================
// USER MANAGEMENT
// Admin panel + user self-management
// ============================================
import { state } from './state.js';
import { showToast, showConfirmDialog } from './utils.js';
import { t } from './i18n.js';

const STATUS_LABELS = {
    NEW:                      { pl: 'NOWE',                     en: 'NEW' },
    WAITING_FOR_CONFIRMATION: { pl: 'OCZEKUJE_POTWIERDZENIA',   en: 'WAITING_FOR_CONFIRMATION' },
    WAITING_FOR_APPROVAL:     { pl: 'OCZEKUJE_ZATWIERDZENIA',   en: 'WAITING_FOR_APPROVAL' },
    ACTIVE:                   { pl: 'AKTYWNE',                  en: 'ACTIVE' },
    DELETED:                  { pl: 'USUNIĘTE',                 en: 'DELETED' },
};

const STATUS_COLORS = {
    NEW:                      '#6c757d',
    WAITING_FOR_CONFIRMATION: '#fd7e14',
    WAITING_FOR_APPROVAL:     '#ffc107',
    ACTIVE:                   '#28a745',
    DELETED:                  '#dc3545',
};

const EMPTY_USAGE_STATS = {
    total_clicks: 0,
    first_click_at: null,
    last_click_at: null,
};

export function initializeUserManagement() {
    const btn = document.getElementById('user-display-btn');
    if (btn) btn.addEventListener('click', () => openUserModal());

    const closeBtn = document.getElementById('user-modal-close');
    if (closeBtn) closeBtn.addEventListener('click', closeUserModal);

    // Close on backdrop click
    const modal = document.getElementById('user-modal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeUserModal();
        });
    }
}

function openUserModal() {
    const modal = document.getElementById('user-modal');
    if (!modal) return;
    modal.classList.add('show');
    loadUserManagementContent();
}

function closeUserModal() {
    const modal = document.getElementById('user-modal');
    if (modal) modal.classList.remove('show');
}

async function loadUserManagementContent() {
    const body  = document.getElementById('user-modal-body');
    const title = document.getElementById('user-modal-title');
    if (!body) return;

    body.innerHTML = `<div class="loading-spinner">${t('loading')}</div>`;

    try {
        const r    = await fetch('user.php?action=list-users', { credentials: 'include' });
        const data = await r.json();

        if (!r.ok || !data.success) {
            body.innerHTML = `<p class="empty-placeholder">${t('errorLoading')}</p>`;
            return;
        }

        const isAdmin = state.userRole === 'ADMIN';
        if (title) title.textContent = isAdmin ? t('userMgmtTitle') : t('myAccountTitle');

        body.innerHTML = isAdmin
            ? buildAdminView(data.users)
            : buildUserView(data.users);

        if (isAdmin) wireAdminActions();
        wireDeleteActions();
    } catch (err) {
        console.error('User management error:', err);
        body.innerHTML = `<p class="empty-placeholder">${t('errorLoading')}</p>`;
    }
}

// ── Admin view ────────────────────────────────────────────────────────────────

function buildAdminView(users) {
    const lang = state.language || 'pl';

    if (!users || users.length === 0) {
        return `<p class="empty-placeholder">${t('noUsers')}</p>`;
    }

    const totalClicks = users.reduce((sum, user) => sum + getUsageStats(user).total_clicks, 0);
    const activeUsers = users.filter(user => getUsageStats(user).total_clicks > 0).length;
    const latestActivityList = users
        .map(user => getUsageStats(user).last_click_at)
        .filter(Boolean)
        .sort();
    const latestActivity = latestActivityList.length ? latestActivityList[latestActivityList.length - 1] : null;

    const rows = users.map(u => {
        const statusLabel = (STATUS_LABELS[u.status] || {})[lang] || u.status;
        const color       = STATUS_COLORS[u.status] || '#6c757d';
        const created     = formatDate(u.created_at);
        const updated     = formatDate(u.updated_at);
        const isSelf      = u.email === state.userEmail;
        const stats       = getUsageStats(u);

        const approveBtn = u.status === 'WAITING_FOR_APPROVAL'
            ? `<button class="btn-primary btn-sm approve-user-btn" data-email="${esc(u.email)}">${t('btnApprove')}</button>`
            : '';
        const deleteBtn = !isSelf || u.role !== 'ADMIN'
            ? `<button class="btn-secondary btn-sm delete-user-btn" data-email="${esc(u.email)}">${t('btnDeleteUser')}</button>`
            : '';

        return `
        <tr class="user-row">
          <td>
            <div class="user-email">${esc(u.email)}</div>
            <div class="user-meta">${esc(u.role)}${isSelf ? ' (' + t('you') + ')' : ''}</div>
          </td>
          <td><span class="status-badge" style="background:${color}">${statusLabel}</span></td>
          <td class="usage-count-cell">${formatCount(stats.total_clicks)}</td>
          <td class="date-cell">${stats.last_click_at ? formatDate(stats.last_click_at) : '—'}</td>
          <td class="date-cell">${created}</td>
          <td class="date-cell">${updated}</td>
          <td class="action-cell">${approveBtn} ${deleteBtn}</td>
        </tr>`;
    }).join('');

    return `
    <div class="usage-stats-card usage-stats-card--admin">
      <h3>${t('usageStatsTitle')}</h3>
      <div class="usage-stats-grid usage-stats-grid--admin">
        <div class="usage-stat-item"><span>${t('usageTotalClicks')}</span><strong>${formatCount(totalClicks)}</strong></div>
        <div class="usage-stat-item"><span>${t('usageActiveUsers')}</span><strong>${formatCount(activeUsers)}</strong></div>
        <div class="usage-stat-item"><span>${t('usageLastActivity')}</span><strong>${latestActivity ? formatDate(latestActivity) : '—'}</strong></div>
      </div>
    </div>
    <div class="user-table-wrapper">
      <table class="user-table">
        <thead>
          <tr>
            <th>${t('colEmail')}</th>
            <th>${t('colStatus')}</th>
            <th>${t('colClicks')}</th>
            <th>${t('colLastActivity')}</th>
            <th>${t('colCreated')}</th>
            <th>${t('colUpdated')}</th>
            <th>${t('colActions')}</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
}

function wireAdminActions() {
    document.querySelectorAll('.approve-user-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const email = btn.dataset.email;
            const ok    = await showConfirmDialog(
                t('confirmApproveTitle'),
                t('confirmApproveMsg', { item: email }),
                t('btnApprove'),
                t('cancelDefault')
            );
            if (!ok) return;

            try {
                const r    = await fetch('user.php?action=approve-user', {
                    method: 'POST', credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email }),
                });
                const data = await r.json();
                if (r.ok && data.success) {
                    showToast(t('userApproved', { item: email }), 'success');
                    loadUserManagementContent();
                } else {
                    showToast(data.error || t('errorGeneral'), 'error');
                }
            } catch (err) {
                showToast(t('errorGeneral'), 'error');
            }
        });
    });
}

// ── User self-view ────────────────────────────────────────────────────────────

function buildUserView(users) {
    const lang = state.language || 'pl';
    const me   = users.find(u => u.email === state.userEmail) || users[0];
    if (!me) return `<p class="empty-placeholder">${t('noUsers')}</p>`;

    const statusLabel = (STATUS_LABELS[me.status] || {})[lang] || me.status;
    const color       = STATUS_COLORS[me.status] || '#6c757d';
    const isDemo      = state.userRole === 'DEMO';
    const stats       = getUsageStats(me);

    return `
    <div class="my-account-card">
      <div class="account-row"><span class="account-label">${t('labelEmail')}</span><span>${esc(me.email)}</span></div>
      <div class="account-row"><span class="account-label">${t('labelRole')}</span><span>${esc(me.role)}</span></div>
      <div class="account-row">
        <span class="account-label">${t('colStatus')}</span>
        <span class="status-badge" style="background:${color}">${statusLabel}</span>
      </div>
      <div class="account-row"><span class="account-label">${t('colCreated')}</span><span>${formatDate(me.created_at)}</span></div>
      <div class="account-row"><span class="account-label">${t('colClicks')}</span><span>${formatCount(stats.total_clicks)}</span></div>
      <div class="account-row"><span class="account-label">${t('colLastActivity')}</span><span>${stats.last_click_at ? formatDate(stats.last_click_at) : '—'}</span></div>
      <div class="usage-stats-card usage-stats-card--self">
        <h3>${t('usageStatsTitle')}</h3>
        <div class="usage-stats-grid">
          <div class="usage-stat-item"><span>${t('usageTotalClicks')}</span><strong>${formatCount(stats.total_clicks)}</strong></div>
          <div class="usage-stat-item"><span>${t('usageFirstClick')}</span><strong>${stats.first_click_at ? formatDate(stats.first_click_at) : '—'}</strong></div>
          <div class="usage-stat-item"><span>${t('usageLastClick')}</span><strong>${stats.last_click_at ? formatDate(stats.last_click_at) : '—'}</strong></div>
        </div>
      </div>
      ${isDemo ? `<p class="form-hint" style="margin-top:16px">${t('demoAccountNote')}</p>` : `
      <div style="margin-top:24px">
        <button class="btn-secondary delete-user-btn" data-email="${esc(me.email)}"
          style="background:#dc3545;border-color:#dc3545;color:#fff">
          🗑️ ${t('btnDeleteAccount')}
        </button>
      </div>`}
    </div>`;
}

// ── Shared ────────────────────────────────────────────────────────────────────

function wireDeleteActions() {
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const email  = btn.dataset.email;
            const isSelf = email === state.userEmail;
            const ok     = await showConfirmDialog(
                t('confirmDeleteUserTitle'),
                t(isSelf ? 'confirmDeleteOwnAccount' : 'confirmDeleteUserMsg', { item: email }),
                t('btnDeleteUser'),
                t('cancelDefault')
            );
            if (!ok) return;

            try {
                const r    = await fetch('user.php?action=delete-user', {
                    method: 'POST', credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email }),
                });
                const data = await r.json();
                if (r.ok && data.success) {
                    if (data.logout) {
                        showToast(t('accountDeleted'), 'success');
                        setTimeout(() => { window.location.href = 'login.html'; }, 1200);
                    } else {
                        showToast(t('userDeleted', { item: email }), 'success');
                        loadUserManagementContent();
                    }
                } else {
                    showToast(data.error || t('errorGeneral'), 'error');
                }
            } catch (err) {
                showToast(t('errorGeneral'), 'error');
            }
        });
    });
}

function formatDate(iso) {
    if (!iso) return '—';
    try {
        return new Date(iso).toLocaleString(state.language === 'pl' ? 'pl-PL' : 'en-GB', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit',
        });
    } catch { return iso; }
}

function formatCount(value) {
    return new Intl.NumberFormat(state.language === 'pl' ? 'pl-PL' : 'en-GB').format(Number(value) || 0);
}

function getUsageStats(user) {
    return {
        ...EMPTY_USAGE_STATS,
        ...(user?.usage_stats || {}),
    };
}


function esc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}


