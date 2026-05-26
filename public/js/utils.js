// ============================================
// UTILITY FUNCTIONS
// ============================================
import { t } from './i18n.js';

/** Generate a URL-safe ID from arbitrary text */
export function generateIdFromText(text) {
    return text
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '_')
        .replace(/[^a-z0-9_]/g, '')
        .replace(/_+/g, '_')
        .replace(/^_|_$/g, '');
}

/** Strip leading emoji so only plain text remains for speech */
export function stripEmojiFromText(text) {
    return text.replace(/^[\p{Emoji}\p{Emoji_Component}]+\s*/gu, '').trim();
}

/** Show a temporary toast notification */
export function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => toast.classList.remove('show'), 3000);
}

/**
 * Show a custom confirm dialog.
 * @returns {Promise<boolean>} resolves true if confirmed
 */
export function showConfirmDialog(title, message, confirmText, cancelText) {
    confirmText = confirmText ?? t('confirmDefault');
    cancelText  = cancelText  ?? t('cancelDefault');
    return new Promise((resolve) => {
        const container = document.createElement('div');
        container.id = 'confirm-dialog-container';
        container.innerHTML = `
            <div class="confirm-dialog-container">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="confirm-dialog-buttons">
                    <button id="confirm-btn" class="btn-primary" style="flex:1;padding:10px;">${confirmText}</button>
                    <button id="cancel-btn" class="btn-secondary" style="flex:1;padding:10px;">${cancelText}</button>
                </div>
            </div>
            <div id="confirm-backdrop" class="confirm-backdrop"></div>
        `;
        document.body.appendChild(container);
        const close = (result) => { container.remove(); resolve(result); };
        document.getElementById('confirm-btn').addEventListener('click', () => close(true));
        document.getElementById('cancel-btn').addEventListener('click', () => close(false));
        document.getElementById('confirm-backdrop').addEventListener('click', () => close(false));
        document.getElementById('confirm-btn').focus();
    });
}

/** Update the status bar text */
export function updateStatus(message) {
    const el = document.getElementById('status');
    if (el) el.textContent = message;
}

/** Fallback copy using a temporary textarea (for Edge / non-secure contexts) */
function fallbackCopyToClipboard(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    const ok = document.execCommand('copy');
    document.body.removeChild(ta);
    return ok;
}

/** Copy text to clipboard and show feedback */
export async function copyToClipboard(text) {
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            if (!fallbackCopyToClipboard(text)) throw new Error('execCommand copy failed');
        }
        showToast(t('copied', { item: text }), 'success');
    } catch (err) {
        // Clipboard API failed (e.g. Edge focus/permission issue) — try fallback
        try {
            if (!fallbackCopyToClipboard(text)) throw new Error('execCommand copy failed');
            showToast(t('copied', { item: text }), 'success');
        } catch (fallbackErr) {
            console.error('Failed to copy:', fallbackErr);
            showToast(t('copyFailed'), 'error');
        }
    }
}
