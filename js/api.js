// ============================================
// API — all HTTP / fetch calls to the backend
// ============================================
import { showToast } from './utils.js';

/** Shared helper – fetch and parse preferences JSON */
async function loadPreferences() {
    const r = await fetch('api.php?action=load-preferences');
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    return r.json();
}

// ── Word data ─────────────────────────────────────────────────────────────────

/** Load word list for the authenticated user */
export async function loadWordList() {
    try {
        const r = await fetch('api.php?action=load-words');
        if (!r.ok) {
            if (r.status === 401) return { categories: {} };
            throw new Error(`HTTP ${r.status}`);
        }
        const data = await r.json();
        if (Array.isArray(data.categories)) data.categories = {};
        return data;
    } catch (err) {
        console.error('Failed to load word list:', err);
        showToast('Błąd ładowania słów', 'error');
        return { categories: {} };
    }
}

/** Persist the current categories object to the backend */
export async function saveToJSON(categories) {
    try {
        const r = await fetch('api.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ categories }),
        });
        const result = await r.json();
        if (!r.ok) {
            showToast('Nie udało się zapisać zmian: ' + (result.error || r.status), 'error');
            return false;
        }
        console.log('✅ Saved:', result);
        return true;
    } catch (err) {
        console.error('Save error:', err);
        showToast('Błąd przy zapisywaniu zmian: ' + err.message, 'error');
        return false;
    }
}

// ── Preferences ───────────────────────────────────────────────────────────────

export async function loadDwellTimePreference() {
    try { return (await loadPreferences()).dwellTimeMs || 2000; }
    catch { return 2000; }
}

export async function loadDwellEnabledPreference() {
    try { return (await loadPreferences()).dwellEnabled !== false; }
    catch { return true; }
}

export async function saveDwellTimePreference(dwellTimeMs) {
    try {
        const r = await fetch('api.php?action=save-preferences', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dwellTimeMs }),
        });
        const result = await r.json();
        if (result.success) { console.log(`✅ Dwell time saved: ${dwellTimeMs}ms`); return true; }
        return false;
    } catch { return false; }
}

export async function saveDwellEnabledPreference(enabled) {
    try {
        const r = await fetch('api.php?action=save-preferences', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dwellEnabled: enabled }),
        });
        const result = await r.json();
        if (result.success) { console.log(`✅ Dwell enabled: ${enabled}`); return true; }
        return false;
    } catch { return false; }
}

export async function loadAlarmDevicePreference() {
    try { return (await loadPreferences()).selectedAlarmDeviceId || ''; }
    catch { return ''; }
}

export async function saveAlarmDevicePreference(deviceId) {
    try {
        const r = await fetch('api.php?action=save-preferences', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ selectedAlarmDeviceId: deviceId }),
        });
        const result = await r.json();
        return result.success === true;
    } catch { return false; }
}

export async function loadAlarmTypePreference() {
    try { return (await loadPreferences()).alarmType || 'high'; }
    catch { return 'high'; }
}

export async function saveAlarmTypePreference(alarmType) {
    try {
        const r = await fetch('api.php?action=save-preferences', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ alarmType }),
        });
        const result = await r.json();
        return result.success === true;
    } catch { return false; }
}

export async function loadAlarmDurationPreference() {
    try { return (await loadPreferences()).alarmDuration || 6; }
    catch { return 6; }
}

export async function saveAlarmDurationPreference(alarmDuration) {
    try {
        const r = await fetch('api.php?action=save-preferences', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ alarmDuration }),
        });
        const result = await r.json();
        return result.success === true;
    } catch { return false; }
}

export async function loadDarkModePreference() {
    try { return (await loadPreferences()).darkModeEnabled === true; }
    catch { return false; }
}

export async function saveDarkModePreference(darkModeEnabled) {
    try {
        const r = await fetch('api.php?action=save-preferences', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ darkModeEnabled }),
        });
        const result = await r.json();
        if (result.success) { console.log(`✅ Dark mode: ${darkModeEnabled}`); return true; }
        return false;
    } catch { return false; }
}


// ── Telegram ──────────────────────────────────────────────────────────────────

export async function loadTelegramConfig() {
    try {
        const prefs = await loadPreferences();
        return { enabled: prefs.telegramEnabled || false };
    } catch { return { enabled: false }; }
}

export async function sendToTelegram(message) {
    try {
        const prefs  = await loadPreferences();
        const chatId = prefs.telegramSelectedChatId || '';

        if (!chatId) {
            showToast('⚠️ Chat ID nie skonfigurowany', 'warning');
            return false;
        }
        const r = await fetch('backend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'send-telegram-message', message, chatId }),
        });
        const result = await r.json();
        if (result.success) { showToast('✓ Wysłane na Telegram', 'success'); return true; }
        showToast('⚠️ Telegram: ' + result.message, 'warning');
        return false;
    } catch (err) {
        console.error('Error sending to Telegram:', err);
        showToast('⚠️ Nie mogę wysłać na Telegram, ale komunikacja działa', 'warning');
        return false;
    }
}


