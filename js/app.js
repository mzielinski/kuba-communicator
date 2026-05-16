// ============================================
// APP — bootstrap / entry point
// ============================================
import { state } from './state.js';
import { showToast } from './utils.js';
import { loadWordList, loadDwellTimePreference, loadDwellEnabledPreference, loadDarkModePreference } from './api.js';
import { checkSession, initializeLogoutButton } from './auth.js';
import { renderCategoryGrid } from './renderer.js';
import { initializeAudioDevices } from './alarm.js';
import { initializeSettingsManagement } from './settingsManagement.js';

async function initializeApp() {
    console.log('Initializing KUBA App\u2026');

    const isLoggedIn = await checkSession();
    if (!isLoggedIn) return;

    const wordList = await loadWordList();
    if (!wordList) {
        showToast('Nie udało się załadować słów. Sprawdź plik words.json', 'error');
        return;
    }

    state.categories   = wordList.categories;
    state.dwellTimeMs  = await loadDwellTimePreference();
    state.dwellEnabled = await loadDwellEnabledPreference();
    state.darkModeEnabled = await loadDarkModePreference();

    // Apply dark mode if enabled
    if (state.darkModeEnabled) {
        document.body.classList.add('dark-mode');
    }

    console.log('Categories:', Object.keys(state.categories).length, '| Dwell:', state.dwellTimeMs + 'ms | Dwell enabled:', state.dwellEnabled, '| Dark mode:', state.darkModeEnabled);

    renderCategoryGrid();
    await initializeAudioDevices();
    await initializeSettingsManagement();
    initializeLogoutButton();

    const voices = window.speechSynthesis.getVoices();
    const pl     = voices.find(v => v.lang.includes('pl'));
    console.log(pl ? `Polish voice: ${pl.name}` : 'Polish voice not found, using default');

    showToast('Aplikacja gotowa!', 'success');
    console.log('\u2705 KUBA App initialized');
}

// ── Startup ───────────────────────────────────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}

window.speechSynthesis.onvoiceschanged = () => console.log('Speech voices loaded');
window.addEventListener('beforeunload', () => window.speechSynthesis.cancel());

if (navigator.mediaDevices) {
    navigator.mediaDevices.addEventListener('devicechange', async () => {
        const { initializeAudioDevices: refresh } = await import('./alarm.js');
        await refresh();
    });
}
