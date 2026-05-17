// ============================================
// APP — bootstrap / entry point
// ============================================
import { state } from './state.js';
import { showToast } from './utils.js';
import { t, applyTranslations } from './i18n.js';
import { loadWordList, loadDwellTimePreference, loadDwellEnabledPreference, loadDarkModePreference, loadAlarmDurationPreference, loadLanguagePreference, loadGlobalWords } from './api.js';
import { checkSession, initializeLogoutButton } from './auth.js';
import { renderCategoryGrid, renderRecentMessages } from './renderer.js';
import { initializeAudioDevices } from './alarm.js';
import { initializeSettingsManagement } from './settingsManagement.js';
import { initializeUserManagement } from './userManagement.js';

async function initializeApp() {
    console.log('Initializing KUBA App…');

    const isLoggedIn = await checkSession();
    if (!isLoggedIn) return;

    // Load language first so UI is translated before rendering
    state.language = await loadLanguagePreference();
    applyTranslations();

    // Show demo mode banner for DEMO users
    if (state.userRole === 'DEMO') {
        showDemoBanner();
    }

    const wordList = await loadWordList();
    if (!wordList) {
        showToast(t('errorLoadingWords'), 'error');
        return;
    }

    state.categories   = wordList.categories;
    const globalData = await loadGlobalWords();
    state.globalWords  = globalData.words || [];
    state.dwellTimeMs  = await loadDwellTimePreference();
    state.dwellEnabled = await loadDwellEnabledPreference();
    state.darkModeEnabled = await loadDarkModePreference();
    state.alarmDuration = await loadAlarmDurationPreference();

    if (state.darkModeEnabled) {
        document.body.classList.add('dark-mode');
    }

    console.log('Categories:', Object.keys(state.categories).length, '| Dwell:', state.dwellTimeMs + 'ms | Dwell enabled:', state.dwellEnabled, '| Dark mode:', state.darkModeEnabled, '| Alarm duration:', state.alarmDuration + 's', '| Language:', state.language, '| Role:', state.userRole);

    renderCategoryGrid();
    renderRecentMessages();
    await initializeAudioDevices();
    await initializeSettingsManagement();
    initializeLogoutButton();
    initializeUserManagement();

    // Hide manage-words button for DEMO users
    if (state.userRole === 'DEMO') {
        const manageBtn = document.getElementById('manage-words-btn');
        if (manageBtn) manageBtn.style.display = 'none';
    }

    const voices = window.speechSynthesis.getVoices();
    const voice  = voices.find(v => v.lang.startsWith(state.language === 'en' ? 'en' : 'pl'));
    console.log(voice ? `Voice found: ${voice.name} (${voice.lang})` : 'No matching voice found, using default');

    showToast(t('appReady'), 'success');
    console.log('✅ KUBA App initialized');
}

function showDemoBanner() {
    const banner = document.getElementById('demo-banner');
    if (banner) {
        banner.style.display = 'flex';
        const link = banner.querySelector('a');
        if (link) link.textContent = t('demoBannerLink');
        const text = banner.querySelector('.demo-banner-text');
        if (text) text.textContent = t('demoBannerText');
    }
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
