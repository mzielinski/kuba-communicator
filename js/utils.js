// ============================================
// UTILITY FUNCTIONS
// ============================================

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
export function showConfirmDialog(title, message, confirmText = 'Potwierdź', cancelText = 'Anuluj') {
    return new Promise((resolve) => {
        const container = document.createElement('div');
        container.id = 'confirm-dialog-container';
        container.innerHTML = `
            <div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:30px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.3);z-index:2000;min-width:380px;max-width:90%;box-sizing:border-box;">
                <h3 style="margin-top:0;color:#333;font-size:18px;">${title}</h3>
                <p style="margin:15px 0;color:#666;line-height:1.5;">${message}</p>
                <div style="display:flex;gap:10px;margin-top:25px;">
                    <button id="confirm-btn" class="btn-primary" style="flex:1;padding:10px;">${confirmText}</button>
                    <button id="cancel-btn" class="btn-secondary" style="flex:1;padding:10px;">${cancelText}</button>
                </div>
            </div>
            <div id="confirm-backdrop" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);z-index:1999;"></div>
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

/** Copy text to clipboard and show feedback */
export async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Skopiowano: ' + text, 'success');
    } catch (err) {
        console.error('Failed to copy:', err);
        showToast('Nie udało się skopiować', 'error');
    }
}
