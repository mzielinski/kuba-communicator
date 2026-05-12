// ============================================
// SPEECH SYNTHESIS
// ============================================
import { state } from './state.js';
import { showToast, updateStatus } from './utils.js';

/** Speak text aloud using the Web Speech API (Polish locale) */
export function speakWord(text) {
    if (state.isSpeaking) window.speechSynthesis.cancel();

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang   = 'pl-PL';
    utterance.rate   = 0.9;
    utterance.pitch  = 1;
    utterance.volume = 1;

    utterance.onstart = () => { state.isSpeaking = true;  updateStatus('Mówię...'); };
    utterance.onend   = () => { state.isSpeaking = false; updateStatus('Gotowe');  };
    utterance.onerror = (e) => {
        console.error('Speech error:', e.error);
        showToast('Błąd odczytu tekstu', 'error');
        state.isSpeaking = false;
        updateStatus('Błąd');
    };

    window.speechSynthesis.speak(utterance);
}
