// ============================================
// RENDERER — category grid & expanded view
// ============================================
import { state } from './state.js';
import { stripEmojiFromText } from './utils.js';
import { t } from './i18n.js';
import { initializeDwellTime } from './dwell.js';
import { handleWordClick } from './wordActions.js';
import { speakWord } from './speech.js';

/** Apply word.size to a button element (number or CSS string) */
function applyWordSize(button, word, defaultSize = '30px') {
    if (!word.size) { button.style.fontSize = defaultSize; return; }
    button.style.fontSize = (typeof word.size === 'number') ? word.size + 'px' : word.size;
}

/** Create a fully wired word button */
function createWordButton(word, extraClass = '', defaultSize = '30px', categoryName = null) {
    const btn = document.createElement('button');
    btn.className = 'word-button' + (extraClass ? ' ' + extraClass : '');
    btn.setAttribute('data-word-id', word.id);
    btn.textContent = word.text;
    btn.setAttribute('type', 'button');

    if (word.color) {
        btn.style.background  = word.color;
        btn.style.boxShadow   = `0 4px 10px ${word.color}40`;
    }
    applyWordSize(btn, word, defaultSize);

    btn.addEventListener('click',   () => handleWordClick(word, categoryName));
    btn.addEventListener('keydown', (e) => {
        if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); handleWordClick(word, categoryName); }
    });
    initializeDwellTime(btn);
    return btn;
}

/** Render the main category grid (or delegate to the expanded view) */
export function renderCategoryGrid() {
    const grid = document.getElementById('categoryGrid');
    grid.innerHTML = '';

    if (state.expandedCategory) {
        grid.classList.add('expanded-view');
        renderExpandedCategoryView(state.expandedCategory);
        return;
    }

    grid.classList.remove('expanded-view');

    const sorted = Object.entries(state.categories).sort((a, b) => a[1].order - b[1].order);

    if (sorted.length === 0) {
        grid.innerHTML = `<div style="color:#999;font-size:18px;">${t('noCategories')}</div>`;
        return;
    }

    sorted.forEach(([catName, catData]) => {
        const tile = document.createElement('div');
        tile.className = 'category-tile' + (catData.expand ? ' expandable-category' : '');
        if (catData.size) tile.classList.add(`size-${catData.size}`);

        if (catData.expand) {
            // Expandable: a single button that opens the full category view
            const btn = document.createElement('button');
            btn.className = 'category-expand-button';
            btn.textContent = catName;
            btn.type = 'button';
            btn.setAttribute('data-category-name', catName);
            btn.addEventListener('click', () => {
                state.expandedCategory = catName;
                const plain = stripEmojiFromText(catName);
                if (plain) speakWord(plain);
                renderCategoryGrid();
            });
            initializeDwellTime(btn);
            tile.appendChild(btn);
        } else {
            // Normal tile: title + all word buttons
            if (catName === 'Podstawowe') tile.classList.add('category-narrow');
            const title = document.createElement('div');
            title.className = 'category-title';
            title.textContent = catName;
            tile.appendChild(title);

            const wordsWrap = document.createElement('div');
            wordsWrap.className = 'category-words';
            catData.words.forEach(word => wordsWrap.appendChild(createWordButton(word, '', '30px', catName)));
            tile.appendChild(wordsWrap);
        }

        grid.appendChild(tile);
    });
}

/** Render the expanded single-category view */
export function renderExpandedCategoryView(catName) {
    const grid    = document.getElementById('categoryGrid');
    const catData = state.categories[catName];
    grid.innerHTML = '';

    if (!catData) {
        state.expandedCategory = null;
        renderCategoryGrid();
        return;
    }

    const container = document.createElement('div');
    container.className = 'expanded-category-view';

    const title = document.createElement('h2');
    title.className = 'expanded-title';
    title.textContent = catName;
    container.appendChild(title);

    const wordsWrap = document.createElement('div');
    wordsWrap.className = 'expanded-words-container';

    // Back button – first item in the grid
    const backBtn = document.createElement('button');
    backBtn.className = 'word-button expanded-word-button back-button-expanded';
    backBtn.textContent = t('backButton');
    backBtn.type = 'button';
    backBtn.style.background = '#28a745';
    backBtn.style.boxShadow  = '0 4px 10px rgba(40,167,69,0.4)';
    const goBack = () => { state.expandedCategory = null; renderCategoryGrid(); };
    backBtn.addEventListener('click', goBack);
    backBtn.addEventListener('keydown', (e) => {
        if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); goBack(); }
    });
    initializeDwellTime(backBtn);
    wordsWrap.appendChild(backBtn);

    catData.words.forEach(word => wordsWrap.appendChild(createWordButton(word, 'expanded-word-button', '20px', catName)));

    container.appendChild(wordsWrap);
    grid.appendChild(container);
}

/** Render the recently clicked messages list */
export function renderRecentMessages() {
    const container = document.getElementById('recent-messages-list');
    if (!container) return;

    container.innerHTML = '';

    if (state.recentlyClickedMessages.length === 0) {
        container.innerHTML = '<div style="color:#999;font-size:12px;padding:0 10px;flex:1;text-align:center;">-</div>';
        return;
    }

    state.recentlyClickedMessages.forEach((msg) => {
        const item = document.createElement('button');
        item.className = 'recent-message-item';
        item.type = 'button';

        const text = document.createElement('div');
        text.className = 'recent-message-text';
        text.textContent = msg.text;

        const meta = document.createElement('div');
        meta.className = 'recent-message-meta';
        meta.textContent = `${msg.category} • ${msg.timestamp}`;

        item.appendChild(text);
        item.appendChild(meta);

        // Make it clickable to replay the message
        item.addEventListener('click', async () => {
            await handleWordClick(msg.word, msg.category === 'N/A' ? null : msg.category);
        });

        // Add keyboard support
        item.addEventListener('keydown', (e) => {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                item.click();
            }
        });

        container.appendChild(item);
    });

    // Auto-scroll to the right (newest message)
    setTimeout(() => {
        container.scrollLeft = container.scrollWidth;
    }, 0);
}
