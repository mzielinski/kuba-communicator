// ============================================
// SPEECH SYNTHESIS
// ============================================
import { state } from './state.js';
import { showToast, updateStatus } from './utils.js';
import { t } from './i18n.js';

/** Speak text aloud using the Web Speech API */
export function speakWord(text) {
    if (state.isSpeaking) window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);

    // Pick the language and a matching voice based on the current UI language
    const langCode = state.language === 'en' ? 'en-GB' : 'pl-PL';
    utterance.lang = langCode;

    const voices = window.speechSynthesis.getVoices();
    const match  = voices.find(v => v.lang.startsWith(state.language === 'en' ? 'en' : 'pl'));
    if (match) utterance.voice = match;

    utterance.rate   = 0.9;
    utterance.pitch  = 1;
    utterance.volume = 1;

    utterance.onstart = () => { state.isSpeaking = true;  updateStatus(t('statusSpeaking')); };
    utterance.onend   = () => { state.isSpeaking = false; updateStatus(t('statusReady'));    };
    utterance.onerror = (e) => {
        console.error('Speech error:', e.error);
        showToast(t('speechError'), 'error');
        state.isSpeaking = false;
        updateStatus(t('statusError'));
    };

    window.speechSynthesis.speak(utterance);
}
