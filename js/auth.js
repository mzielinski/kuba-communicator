// ============================================
// AUTHENTICATION
// ============================================
import { showToast, showConfirmDialog } from './utils.js';
import { t } from './i18n.js';

/**
 * Verify the user's session; redirect to login.html if unauthenticated.
 * @returns {Promise<boolean>}
 */
export async function checkSession() {
    try {
        const r      = await fetch('login.php?action=check-session');
        const result = await r.json();

        if (!result.loggedIn) {
            window.location.href = 'login.html';
            return false;
        }

        if (result.user?.username) {
            const el = document.getElementById('user-display');
            if (el) el.textContent = `\u{1F464} ${result.user.username}`;
        }

        return true;
    } catch (err) {
        console.error('Session check error:', err);
        window.location.href = 'login.html';
        return false;
    }
}

/** Log out the current user and redirect to login.html */
export async function handleLogout() {
    try {
        const r = await fetch('login.php?action=logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({}),
        });
        if (r.ok) {
            showToast(t('loggedOut'), 'success');
            setTimeout(() => { window.location.href = 'login.html'; }, 500);
        }
    } catch (err) {
        console.error('Logout error:', err);
        showToast(t('logoutError'), 'error');
    }
}

/** Wire up the logout button with a confirmation dialog */
export function initializeLogoutButton() {
    const btn = document.getElementById('logout-btn');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        const ok = await showConfirmDialog(
            t('confirmLogoutTitle'),
            t('confirmLogoutMsg'),
            t('confirmLogoutOk'),
            t('cancelDefault'),
        );
        if (ok) handleLogout();
    });
}
