// ============================================
// WORD ACTIONS — pipeline executed on word click
// ============================================
import { stripEmojiFromText, copyToClipboard } from './utils.js';
import { speakWord } from './speech.js';
import { playAlarm } from './alarm.js';
import { loadTelegramConfig, sendToTelegram } from './api.js';
import { state } from './state.js';
import { renderRecentMessages } from './renderer.js';

/**
 * Full action pipeline for a word button click:
 *  1. Speak the word (emoji stripped)
 *  2. Copy to clipboard
 *  3. Forward to Telegram (if configured)
 *  4. Visual feedback on the button
 *  5. Track in recent messages
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

    // Track the clicked message
    addToRecentMessages(word, categoryName);
}

/**
 * Add a message to the recently clicked messages list (keep last 10, skip duplicates)
 */
function addToRecentMessages(word, categoryName) {
    const timestamp = new Date().toLocaleTimeString('pl-PL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    
    // Remove any existing messages with the same text and category
    state.recentlyClickedMessages = state.recentlyClickedMessages.filter(
        msg => !(msg.text === word.text && msg.category === (categoryName || 'N/A'))
    );

    state.recentlyClickedMessages.unshift({
        word, // Store the full word object for replay
        text: word.text,
        category: categoryName || 'N/A',
        timestamp
    });

    // Keep only the last 10 messages
    if (state.recentlyClickedMessages.length > 10) {
        state.recentlyClickedMessages = state.recentlyClickedMessages.slice(0, 10);
    }

    // Update the UI
    renderRecentMessages();
}
