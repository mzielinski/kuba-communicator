// ============================================
// KEYBOARD — full-screen virtual on-screen keyboard
// ============================================
import { speakWord } from './speech.js';
import { state } from './state.js';
import { t } from './i18n.js';
import { initializeDwellTime } from './dwell.js';

let keyboardOverlay = null;

const NUMBERS_ROW   = ['1','2','3','4','5','6','7','8','9','0'];
const SPECIAL_ROWS  = [
    ['!', '?', '.', ',', ':', ';', '-', '_', '(', ')'],
    ['@', '#', '&', '+', '=', '/', '\\', '"', "'", '%'],
];

const LETTER_ROWS = [
    ['q','w','e','r','t','y','u','i','o','p'],
    ['a','s','d','f','g','h','j','k','l'],
    ['z','x','c','v','b','n','m'],
];

const POLISH_MAP = {
    'a':'ą', 'e':'ę', 'o':'ó', 's':'ś',
    'z':'ź', 'x':'ż', 'c':'ć', 'n':'ń', 'l':'ł'
};

export function openVirtualKeyboard() {
    if (keyboardOverlay) return;

    let currentText = '';
    let polishMode  = false;
    let capsLock    = false;
    let numbersMode = false;

    keyboardOverlay = document.createElement('div');
    keyboardOverlay.id = 'virtual-keyboard-overlay';
    document.body.appendChild(keyboardOverlay);

    render();

    function render() {
        keyboardOverlay.innerHTML = '';
        keyboardOverlay.className = 'vk-fullscreen';

        const isPolishLang = state.language === 'pl';

        /* ── Display bar ── */
        const displayBar = document.createElement('div');
        displayBar.className = 'vk-display-bar';

        const speakBarBtn = document.createElement('button');
        speakBarBtn.className = 'vk-action-btn vk-speak-btn';
        speakBarBtn.type = 'button';
        speakBarBtn.innerHTML = '🔊 ' + (t('virtualKeyboardSpeak') || 'Mów');
        speakBarBtn.addEventListener('click', () => {
            const text = currentText.trim();
            if (text) speakWord(text);
        });
        initializeDwellTime(speakBarBtn);
        displayBar.appendChild(speakBarBtn);

        const displayText = document.createElement('div');
        displayText.className = 'vk-display-text';
        displayText.id = 'vk-display';
        displayText.textContent = currentText || '';
        displayBar.appendChild(displayText);

        const closeBtn = document.createElement('button');
        closeBtn.className = 'vk-close-btn';
        closeBtn.type = 'button';
        closeBtn.textContent = '✕';
        closeBtn.addEventListener('click', closeVirtualKeyboard);
        initializeDwellTime(closeBtn);
        displayBar.appendChild(closeBtn);
        keyboardOverlay.appendChild(displayBar);

        /* ── Key rows ── */
        const keysArea = document.createElement('div');
        keysArea.className = 'vk-keys-area';

        if (numbersMode) {
            const numRow = document.createElement('div');
            numRow.className = 'vk-row';
            NUMBERS_ROW.forEach(key => numRow.appendChild(makeLetterKey(key, key)));
            keysArea.appendChild(numRow);

            SPECIAL_ROWS.forEach(row => {
                const rowEl = document.createElement('div');
                rowEl.className = 'vk-row';
                row.forEach(key => rowEl.appendChild(makeLetterKey(key, key)));
                keysArea.appendChild(rowEl);
            });
        } else {

        LETTER_ROWS.forEach(row => {
            const rowEl = document.createElement('div');
            rowEl.className = 'vk-row';

            row.forEach(key => {
                const hasPolish = !!POLISH_MAP[key];
                let ch = key;
                if (polishMode && hasPolish) ch = POLISH_MAP[key];
                if (capsLock) ch = ch.toUpperCase();

                const disabled = polishMode && !hasPolish;
                rowEl.appendChild(makeLetterKey(key, ch, disabled));
            });

            keysArea.appendChild(rowEl);
        });

        } // end else (letters only)

        keyboardOverlay.appendChild(keysArea);

        /* ── Action row 1: utility buttons ── */
        const actionBar = document.createElement('div');
        actionBar.className = 'vk-action-bar';

        const makeBtn = (label, cls, handler) => {
            const btn = document.createElement('button');
            btn.className = `vk-action-btn ${cls}`;
            btn.type = 'button';
            btn.innerHTML = label;
            btn.addEventListener('click', handler);
            initializeDwellTime(btn);
            return btn;
        };

        actionBar.appendChild(makeBtn(`⇪ CAPS`, `vk-caps-btn${capsLock ? ' vk-active' : ''}`, () => {
            capsLock = !capsLock; render();
        }));

        actionBar.appendChild(makeBtn('123 / !?', `vk-num-btn${numbersMode ? ' vk-active' : ''}`, () => {
            numbersMode = !numbersMode; render();
        }));

        // PL button only when app language is Polish
        if (isPolishLang) {
            actionBar.appendChild(makeBtn('PL', `vk-pl-btn${polishMode ? ' vk-active' : ''}`, () => {
                polishMode = !polishMode; render();
            }));
        }

        actionBar.appendChild(makeBtn(t('virtualKeyboardSpace') || 'Spacja', 'vk-space-btn', () => {
            currentText += ' '; updateDisplay();
        }));

        actionBar.appendChild(makeBtn('⌫', 'vk-backspace-btn', () => {
            currentText = currentText.slice(0, -1); updateDisplay();
        }));

        actionBar.appendChild(makeBtn('⌫ ' + (t('virtualKeyboardDelWord') || 'Słowo'), 'vk-delword-btn', () => {
            const trimmed = currentText.trimEnd();
            const lastSpace = trimmed.lastIndexOf(' ');
            currentText = lastSpace >= 0 ? trimmed.slice(0, lastSpace + 1) : '';
            updateDisplay();
        }));

        actionBar.appendChild(makeBtn('✕', 'vk-clear-btn', () => {
            currentText = ''; updateDisplay();
        }));

        keyboardOverlay.appendChild(actionBar);
    }

    function makeLetterKey(key, displayChar, disabled = false) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = displayChar;

        if (disabled) {
            btn.className = 'vk-key vk-key-disabled';
            btn.disabled = true;
        } else {
            btn.className = 'vk-key';
            btn.addEventListener('click', () => { currentText += displayChar; updateDisplay(); });
            initializeDwellTime(btn);
        }
        return btn;
    }

    function updateDisplay() {
        const el = document.getElementById('vk-display');
        if (el) el.textContent = currentText || '';
    }
}

export function closeVirtualKeyboard() {
    if (keyboardOverlay) {
        keyboardOverlay.remove();
        keyboardOverlay = null;
    }
}
