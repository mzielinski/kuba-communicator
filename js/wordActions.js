// ============================================
// WORD ACTIONS — pipeline executed on word click
// ============================================
import { stripEmojiFromText, copyToClipboard } from './utils.js';
import { speakWord } from './speech.js';
import { playAlarm } from './alarm.js';
import { loadTelegramConfig, sendToTelegram } from './api.js';

/**
 * Full action pipeline for a word button click:
 *  1. Speak the word (emoji stripped)
 *  2. Copy to clipboard
 *  3. Forward to Telegram (if configured)
 *  4. Visual feedback on the button
 */
export async function handleWordClick(word, categoryName = null) {
    if (word.id === 'alarm') {
        await playAlarm();
        return;
    }

    console.log('Word clicked:', word.id, word.text, 'Category:', categoryName);

    const plain = stripEmojiFromText(word.text);
    if (plain) speakWord(plain);

    await copyToClipboard(word.text);

    try {
        const telegram = await loadTelegramConfig();
        if (telegram.enabled) sendToTelegram(word.text, categoryName).catch(e => console.warn('Telegram error:', e));
    } catch (err) {
        console.warn('Telegram config error:', err);
    }

    const btn = document.querySelector(`[data-word-id="${word.id}"]`);
    if (btn) {
        btn.classList.add('active-speech');
        setTimeout(() => btn.classList.remove('active-speech'), 600);
    }
}
