// ============================================
// WORD MANAGEMENT — modal, CRUD for words & categories
// ============================================
import { state } from './state.js';
import { showToast, showConfirmDialog, generateIdFromText } from './utils.js';
import { renderCategoryGrid } from './renderer.js';
import { loadWordList, saveToJSON, saveDwellTimePreference, saveDwellEnabledPreference } from './api.js';
import { initializeAlarmDeviceSelector } from './alarm.js';

/** Initialize the entire word-management modal */
export function initializeWordManagement() {
    // Element refs
    const modal          = document.getElementById('word-modal');
    const manageBtn      = document.getElementById('manage-words-btn');
    const closeBtn       = document.getElementById('modal-close');
    const cancelBtn      = document.getElementById('modal-cancel');
    const saveAllBtn     = document.getElementById('save-all-changes-btn');
    const addWordBtn     = document.getElementById('add-word-btn');
    const catSelect      = document.getElementById('category-select');
    const posSelect      = document.getElementById('position-select');
    const useColorChk    = document.getElementById('use-color-checkbox');
    const colorInput     = document.getElementById('word-color-input');
    const tabBtns        = document.querySelectorAll('.tab-btn');
    const tabContents    = document.querySelectorAll('.tab-content');
    const wordsListEl    = document.getElementById('words-list-container');
    const wordsCatSel    = document.getElementById('words-category-select');

    // ── Helpers ───────────────────────────────────────────────────────────────

    function populateWordsCatSelect() {
        const prev = wordsCatSel.value;
        wordsCatSel.innerHTML = '<option value="">-- Wybierz kategorię --</option>';
        sortedCategories().forEach(([name]) => {
            const opt = document.createElement('option');
            opt.value = name; opt.textContent = name;
            wordsCatSel.appendChild(opt);
        });
        if (prev && state.categories[prev]) { wordsCatSel.value = prev; state.selectedWordsCategory = prev; }
        else state.selectedWordsCategory = null;
    }

    function updateCatSelect() {
        while (catSelect.options.length > 1) catSelect.remove(1);
        sortedCategories().forEach(([name]) => {
            const opt = document.createElement('option');
            opt.value = name; opt.textContent = name;
            catSelect.appendChild(opt);
        });
        populateWordsCatSelect();
    }

    function sortedCategories() {
        return Object.entries(state.categories).sort((a, b) => a[1].order - b[1].order);
    }

    // ── Tabs ──────────────────────────────────────────────────────────────────

    wordsCatSel.addEventListener('change', () => {
        state.selectedWordsCategory = wordsCatSel.value || null;
        renderWordsList();
    });

    tabBtns.forEach(btn => btn.addEventListener('click', () => {
        const tab = btn.getAttribute('data-tab');
        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(tab).classList.add('active');
        if (tab === 'words-tab')      { populateWordsCatSelect(); renderWordsList(); }
        if (tab === 'categories-tab') renderCategoriesManagement();
        if (tab !== 'words-tab')      state.editingWord = null;
    }));

    // Seed the add-word category select
    sortedCategories().forEach(([name]) => {
        const opt = document.createElement('option');
        opt.value = name; opt.textContent = name;
        catSelect.appendChild(opt);
    });

    catSelect.addEventListener('change', () => {
        posSelect.innerHTML = '<option value="">-- Wybierz pozycję --</option>';
        const cat = catSelect.value;
        if (!cat) return;
        const words = state.categories[cat].words;
        const endOpt = document.createElement('option');
        endOpt.value = words.length;
        endOpt.textContent = `Na końcu (pozycja ${words.length})`;
        posSelect.appendChild(endOpt);
        words.forEach((w, i) => {
            const opt = document.createElement('option');
            opt.value = i; opt.textContent = `Pozycja ${i} (przed "${w.text}")`;
            posSelect.appendChild(opt);
        });
    });

    useColorChk.addEventListener('change', () => { colorInput.disabled = !useColorChk.checked; });
    colorInput.disabled = !useColorChk.checked;

    // ── Modal open / close ────────────────────────────────────────────────────

    manageBtn.addEventListener('click', () => {
        modal.classList.add('show');
        populateWordsCatSelect();
        renderWordsList();
        renderCategoriesManagement();
        loadTelegramSettingsIntoUI();
    });

    const closeModal = () => {
        modal.classList.remove('show');
        document.getElementById('word-text-input').value = '';
        state.editingWord = null;
        document.getElementById('edit-word-dialog-container')?.remove();
    };

    const closeWithReload = async () => {
        const ok = await showConfirmDialog(
            '\u26A0\uFE0F Odrzuć zmiany',
            'Czy na pewno chcesz zamknąć bez zapisywania? Wszystkie zmiany zostaną stracone.',
            'Odrzuć', 'Kontynuuj edycję',
        );
        if (!ok) return;
        const data = await loadWordList();
        state.categories = data.categories;
        renderCategoryGrid(); renderCategoriesManagement(); closeModal();
        showToast('\u274C Zmiany odrzucone, załadowano dane z pliku', 'warning');
    };

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeWithReload);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    saveAllBtn.addEventListener('click', async () => {
        try {
            await saveToJSON(state.categories);
            renderCategoryGrid();
            showToast('\u2705 Wszystkie zmiany zapisane!', 'success');
            closeModal();
        } catch (err) {
            showToast('Błąd przy zapisywaniu zmian', 'error');
        }
    });

    // ── Words list ────────────────────────────────────────────────────────────

    function renderWordsList() {
        wordsListEl.innerHTML = '';
        const addForm = document.getElementById('add-word-form-simple');
        const cat = state.selectedWordsCategory;

        if (!cat || !state.categories[cat]) {
            wordsListEl.innerHTML = '<p style="color:#999;text-align:center;padding:20px 0;margin:0;">Wybierz kategorię aby zobaczyć słowa</p>';
            if (addForm) addForm.style.display = 'none';
            return;
        }
        if (addForm) addForm.style.display = 'block';

        const { words } = state.categories[cat];
        if (words.length === 0) {
            wordsListEl.innerHTML = '<p style="color:#999;text-align:center;padding:20px 0;margin:0;">Brak słów w tej kategorii.</p>';
            return;
        }

        words.forEach((word, idx) => {
            const item = document.createElement('div');
            item.style.cssText = `background:white;padding:10px;margin:6px 0;border-radius:6px;border-left:4px solid ${word.color || '#667eea'};display:flex;justify-content:space-between;align-items:center;`;

            const info = document.createElement('div');
            info.innerHTML = `
                <div style="font-weight:600;color:#333;font-size:14px;">${word.text}</div>
                <div style="font-size:12px;color:#999;margin-top:3px;">${word.size ? 'Rozmiar: ' + word.size : 'Rozmiar: domyślny'}${word.color ? ' | \u{1F3A8} ' + word.color : ''}</div>
            `;

            const btnWrap = document.createElement('div');
            btnWrap.style.cssText = 'display:flex;gap:5px;flex-shrink:0;';

            const bs = (bg, dis = false) => `padding:6px 10px;background:${bg};color:white;border:none;border-radius:4px;cursor:${dis ? 'default' : 'pointer'};font-size:12px;${dis ? 'opacity:0.5;' : ''}`;

            const upBtn = document.createElement('button');
            upBtn.textContent = '\u2191'; upBtn.title = 'Przesuń wyżej';
            upBtn.disabled = idx === 0; upBtn.style.cssText = bs('#28a745', idx === 0);
            upBtn.addEventListener('click', () => {
                [words[idx - 1], words[idx]] = [words[idx], words[idx - 1]];
                renderCategoryGrid(); renderWordsList(); showToast('Słowo przesunięte w górę', 'success');
            });

            const downBtn = document.createElement('button');
            downBtn.textContent = '\u2193'; downBtn.title = 'Przesuń niżej';
            downBtn.disabled = idx === words.length - 1; downBtn.style.cssText = bs('#28a745', idx === words.length - 1);
            downBtn.addEventListener('click', () => {
                [words[idx + 1], words[idx]] = [words[idx], words[idx + 1]];
                renderCategoryGrid(); renderWordsList(); showToast('Słowo przesunięte w dół', 'success');
            });

            const editBtn = document.createElement('button');
            editBtn.textContent = '\u270F\uFE0F'; editBtn.title = 'Edytuj';
            editBtn.style.cssText = bs('#667eea');
            editBtn.addEventListener('click', () => openEditWordDialog(cat, idx));

            const delBtn = document.createElement('button');
            delBtn.textContent = '\u{1F5D1}\uFE0F'; delBtn.title = 'Usuń';
            delBtn.style.cssText = bs('#ff6464');
            delBtn.addEventListener('click', async () => {
                const ok = await showConfirmDialog('\u{1F5D1}\uFE0F Usuń słowo', `Czy usunąć "${word.text}"?`, 'Usuń', 'Anuluj');
                if (ok) {
                    state.categories[cat].words.splice(idx, 1);
                    renderCategoryGrid(); renderWordsList(); showToast(`"${word.text}" usunięte!`, 'success');
                }
            });

            btnWrap.append(upBtn, downBtn, editBtn, delBtn);
            item.append(info, btnWrap);
            wordsListEl.appendChild(item);
        });
    }

    function openEditWordDialog(catName, wordIdx) {
        state.editingWord = { category: catName, index: wordIdx };
        document.getElementById('edit-word-dialog-container')?.remove();

        const word = state.categories[catName].words[wordIdx];
        const container = document.createElement('div');
        container.id = 'edit-word-dialog-container';
        container.innerHTML = `
            <div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:30px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.3);z-index:2000;min-width:400px;max-width:90%;box-sizing:border-box;">
                <h3 style="margin-top:0;color:#333;">\u270F\uFE0F Edytuj słowo</h3>
                <div style="margin:15px 0;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Tekst słowa:</label>
                    <input type="text" id="popup-word-text" value="${word.text.replace(/"/g, '&quot;')}" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;box-sizing:border-box;">
                </div>
                <div style="margin:15px 0;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Kolor:</label>
                    <input type="color" id="popup-word-color" value="${word.color || '#667eea'}" style="width:100%;height:40px;border:1px solid #ddd;border-radius:4px;cursor:pointer;box-sizing:border-box;">
                </div>
                <div style="margin:15px 0;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Rozmiar fontu (px):</label>
                    <input type="number" id="popup-word-size" value="${word.size ? parseInt(word.size) : ''}" placeholder="domyślny (30px)" min="8" max="200" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;box-sizing:border-box;">
                </div>
                <div style="display:flex;gap:10px;margin-top:25px;">
                    <button id="popup-save-word" class="btn-primary" style="flex:1;padding:10px;">Zapisz</button>
                    <button id="popup-cancel-word" class="btn-secondary" style="flex:1;padding:10px;">Anuluj</button>
                </div>
            </div>
            <div id="edit-word-backdrop" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);z-index:1999;"></div>
        `;
        document.body.appendChild(container);

        document.getElementById('popup-save-word').addEventListener('click', () => {
            const { category: c, index: i } = state.editingWord;
            const w = state.categories[c].words[i];
            const newText = document.getElementById('popup-word-text').value.trim();
            if (!newText) { showToast('Tekst nie może być pusty', 'error'); return; }
            w.text  = newText;
            w.color = document.getElementById('popup-word-color').value;
            const sz = document.getElementById('popup-word-size').value;
            if (sz && parseInt(sz) > 0) w.size = parseInt(sz) + 'px'; else delete w.size;
            renderCategoryGrid(); renderWordsList();
            container.remove(); state.editingWord = null;
            showToast(`"${newText}" zaktualizowane!`, 'success');
        });

        const closeDialog = () => { container.remove(); state.editingWord = null; };
        document.getElementById('popup-cancel-word').addEventListener('click', closeDialog);
        document.getElementById('edit-word-backdrop').addEventListener('click', closeDialog);
        document.getElementById('popup-word-text').focus();
        document.getElementById('popup-word-text').select();
    }

    // Add word
    addWordBtn.addEventListener('click', () => {
        const cat  = state.selectedWordsCategory;
        const text = document.getElementById('word-text-input').value.trim();
        if (!cat)  { showToast('Proszę wybrać kategorię', 'error'); return; }
        if (!text) { showToast('Proszę wprowadzić tekst słowa', 'error'); return; }
        state.categories[cat].words.push({ id: generateIdFromText(text), text });
        renderCategoryGrid(); renderWordsList();
        const input = document.getElementById('word-text-input');
        input.value = ''; input.focus();
        showToast(`"${text}" dodane!`, 'success');
    });

    document.getElementById('word-text-input').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); addWordBtn.click(); }
    });

    // ── Category management ───────────────────────────────────────────────────

    const catNameInput   = document.getElementById('new-category-name');
    const addCatBtn      = document.getElementById('add-category-btn');
    const catListEl      = document.getElementById('categories-management-list');

    function renderCategoriesManagement() {
        catListEl.innerHTML = '';
        const cats = sortedCategories();

        if (cats.length === 0) {
            catListEl.innerHTML = '<p style="color:#999;text-align:center;padding:20px 0;">Brak kategorii</p>';
            return;
        }

        const list = document.createElement('div');
        cats.forEach(([name, data], idx) => {
            const item = document.createElement('div');
            item.style.cssText = 'background:white;padding:10px;margin:8px 0;border-radius:6px;border-left:4px solid #667eea;display:flex;justify-content:space-between;align-items:center;';

            const info = document.createElement('div');
            info.innerHTML = `
                <div style="font-weight:600;color:#333;font-size:14px;">${name}</div>
                <div style="font-size:12px;color:#999;margin-top:4px;">${data.words.length} słów | Rozmiar: ${data.size}</div>
            `;

            const btnWrap = document.createElement('div');
            btnWrap.style.cssText = 'display:flex;gap:5px;flex-wrap:wrap;';

            const bs = (bg, dis = false) => `padding:6px 10px;background:${bg};color:white;border:none;border-radius:4px;cursor:${dis ? 'default' : 'pointer'};font-size:12px;${dis ? 'opacity:0.5;' : ''}`;
            const first = idx === 0, last = idx === cats.length - 1;

            const upBtn = document.createElement('button');
            upBtn.textContent = '\u2191'; upBtn.disabled = first; upBtn.style.cssText = bs('#28a745', first);
            upBtn.addEventListener('click', () => moveCatUp(name));

            const downBtn = document.createElement('button');
            downBtn.textContent = '\u2193'; downBtn.disabled = last; downBtn.style.cssText = bs('#28a745', last);
            downBtn.addEventListener('click', () => moveCatDown(name));

            const editBtn = document.createElement('button');
            editBtn.textContent = '\u270F\uFE0F'; editBtn.style.cssText = bs('#667eea');
            editBtn.addEventListener('click', () => openEditCategoryDialog(name, data));

            const delBtn = document.createElement('button');
            delBtn.textContent = '\u{1F5D1}\uFE0F'; delBtn.style.cssText = bs('#ff6464');
            delBtn.addEventListener('click', () => deleteCat(name));

            btnWrap.append(upBtn, downBtn, editBtn, delBtn);
            item.append(info, btnWrap);
            list.appendChild(item);
        });
        catListEl.appendChild(list);
    }

    addCatBtn.addEventListener('click', () => {
        const name = catNameInput.value.trim();
        if (!name) { showToast('Wpisz nazwę kategorii', 'error'); return; }
        if (state.categories[name]) { showToast('Kategoria już istnieje', 'error'); return; }
        state.categories[name] = { order: Object.keys(state.categories).length + 1, size: 'medium', cols: 2, rows: 1, expand: false, words: [] };
        renderCategoryGrid(); renderCategoriesManagement(); updateCatSelect();
        catNameInput.value = '';
        showToast(`\u2705 Kategoria "${name}" dodana!`, 'success');
    });

    function moveCatUp(name) {
        const entries = sortedCategories();
        const idx = entries.findIndex(([n]) => n === name);
        if (idx <= 0) { showToast('Kategoria jest już na górze', 'error'); return; }
        const prev = entries[idx - 1];
        [state.categories[name].order, state.categories[prev[0]].order] = [state.categories[prev[0]].order, state.categories[name].order];
        renderCategoryGrid(); renderCategoriesManagement(); showToast('Kategoria przesunięta w górę', 'success');
    }

    function moveCatDown(name) {
        const entries = sortedCategories();
        const idx = entries.findIndex(([n]) => n === name);
        if (idx >= entries.length - 1) { showToast('Kategoria jest już na dole', 'error'); return; }
        const next = entries[idx + 1];
        [state.categories[name].order, state.categories[next[0]].order] = [state.categories[next[0]].order, state.categories[name].order];
        renderCategoryGrid(); renderCategoriesManagement(); showToast('Kategoria przesunięta w dół', 'success');
    }

    async function deleteCat(name) {
        const ok = await showConfirmDialog('\u{1F5D1}\uFE0F Usuń kategorię', `Usunąć kategorię "${name}" ze wszystkimi słowami?`, 'Usuń', 'Anuluj');
        if (!ok) return;
        delete state.categories[name];
        renderCategoryGrid(); renderCategoriesManagement(); updateCatSelect();
        showToast(`\u2705 Kategoria "${name}" usunięta!`, 'success');
    }

    function openEditCategoryDialog(catName, catData) {
        const container = document.createElement('div');
        container.id = 'edit-category-dialog-container';
        container.innerHTML = `
            <div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:30px;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.3);z-index:2000;min-width:400px;max-width:90%;">
                <h3 style="margin-top:0;color:#333;">Edytuj kategorię: ${catName}</h3>
                <div style="margin:15px 0;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Nazwa kategorii:</label>
                    <input type="text" id="edit-cat-name" value="${catName}" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;box-sizing:border-box;">
                </div>
                <div style="margin:15px 0;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Rozmiar wyświetlania:</label>
                    <select id="edit-cat-size" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;box-sizing:border-box;">
                        <option value="small" ${catData.size === 'small' ? 'selected' : ''}>Mały (small)</option>
                        <option value="medium" ${catData.size === 'medium' ? 'selected' : ''}>Średni (medium)</option>
                        <option value="large" ${catData.size === 'large' ? 'selected' : ''}>Duży (large)</option>
                    </select>
                </div>
                <div style="margin:15px 0;">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" id="edit-cat-expand" ${catData.expand ? 'checked' : ''}>
                        <span style="color:#333;">Rozwijalna kategoria (otwiera osobny widok)</span>
                    </label>
                </div>
                <div style="display:flex;gap:10px;margin-top:25px;">
                    <button id="save-edit-cat-btn" class="btn-primary" style="flex:1;padding:10px;">Zapisz</button>
                    <button id="cancel-edit-cat-btn" class="btn-secondary" style="flex:1;padding:10px;">Anuluj</button>
                </div>
            </div>
            <div id="edit-cat-backdrop" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.3);z-index:1999;"></div>
        `;
        document.body.appendChild(container);

        const close = () => container.remove();
        document.getElementById('save-edit-cat-btn').addEventListener('click', () => {
            const newName   = document.getElementById('edit-cat-name').value.trim();
            const newSize   = document.getElementById('edit-cat-size').value;
            const newExpand = document.getElementById('edit-cat-expand').checked;
            if (!newName) { showToast('Nazwa nie może być pusta', 'error'); return; }
            if (newName !== catName && state.categories[newName]) { showToast('Kategoria z taką nazwą już istnieje', 'error'); return; }
            if (newName !== catName) { state.categories[newName] = state.categories[catName]; delete state.categories[catName]; }
            state.categories[newName].size   = newSize;
            state.categories[newName].expand = newExpand;
            renderCategoryGrid(); renderCategoriesManagement(); updateCatSelect();
            close(); showToast(`\u2705 Kategoria "${newName}" zaktualizowana!`, 'success');
        });
        document.getElementById('cancel-edit-cat-btn').addEventListener('click', close);
        document.getElementById('edit-cat-backdrop').addEventListener('click', close);
        document.getElementById('edit-cat-name').focus();
        document.getElementById('edit-cat-name').select();
    }


    const catTabBtn = document.querySelector('[data-tab="categories-tab"]');
    if (catTabBtn) catTabBtn.addEventListener('click', renderCategoriesManagement);
    renderCategoriesManagement();

    // ── Settings tab ──────────────────────────────────────────────────────────

    const dwellToggle   = document.getElementById('dwell-enabled-toggle');
    const dwellSlider   = document.getElementById('dwell-time-slider');
    const dwellDisplay  = document.getElementById('dwell-time-display');
    const dwellSettings = document.getElementById('dwell-time-settings');
    const alarmSel      = document.getElementById('alarm-output-select');

    initializeAlarmDeviceSelector(alarmSel);
    
    // Initialize dwell toggle
    if (dwellToggle) {
        dwellToggle.checked = state.dwellEnabled;
        
        // Show/hide dwell time settings based on dwell enabled state
        const updateDwellSettingsVisibility = () => {
            if (dwellSettings) {
                dwellSettings.style.display = dwellToggle.checked ? 'block' : 'none';
            }
        };
        updateDwellSettingsVisibility();
        
        dwellToggle.addEventListener('change', async (e) => {
            state.dwellEnabled = e.target.checked;
            await saveDwellEnabledPreference(state.dwellEnabled);
            updateDwellSettingsVisibility();
            showToast(e.target.checked ? '✓ Patrzenie włączone' : '✓ Patrzenie wyłączone', 'success');
        });
    }
    
    dwellSlider.value = state.dwellTimeMs;
    dwellDisplay.textContent = (state.dwellTimeMs / 1000).toFixed(1) + 's';

    const updateGrad = () => {
        const pct = (dwellSlider.value - dwellSlider.min) / (dwellSlider.max - dwellSlider.min) * 100;
        dwellSlider.style.background = `linear-gradient(to right,#667eea 0%,#667eea ${pct}%,#e0e0e0 ${pct}%,#e0e0e0 100%)`;
    };
    updateGrad();

    dwellSlider.addEventListener('input', (e) => {
        state.dwellTimeMs = parseInt(e.target.value);
        dwellDisplay.textContent = (state.dwellTimeMs / 1000).toFixed(1) + 's';
        updateGrad();
        saveDwellTimePreference(state.dwellTimeMs);
    });

    // ── Telegram settings ─────────────────────────────────────────────────────

    const telegramToggle  = document.getElementById('telegram-toggle');
    const chatIdInput     = document.getElementById('telegram-chat-id-input');
    const telegramTestBtn = document.getElementById('telegram-test-btn');
    const telegramStatus  = document.getElementById('telegram-status');

    if (!telegramToggle) return;

    async function loadTelegramSettingsIntoUI() {
        try {
            const prefs = await fetch('api.php?action=load-preferences').then(r => r.json());
            telegramToggle.checked = prefs.telegramEnabled || false;
            if (chatIdInput) chatIdInput.value = prefs.telegramChatId || '';
            syncTelegramUI();
        } catch (err) {
            console.warn('Could not load Telegram settings:', err);
        }
    }

    function syncTelegramUI() {
        if (telegramTestBtn) telegramTestBtn.disabled = !telegramToggle.checked;
    }

    async function saveTelegramConfig(enabled, chatId) {
        const r = await fetch('api.php?action=save-telegram-config', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ telegramEnabled: enabled, telegramChatId: chatId }),
        });
        return r.json();
    }

    telegramToggle.addEventListener('change', async (e) => {
        try {
            const result = await saveTelegramConfig(e.target.checked, chatIdInput?.value || '');
            if (result.success) {
                showToast(e.target.checked ? '\u2705 Telegram włączony' : '\u274C Telegram wyłączony', 'success');
                syncTelegramUI();
            } else {
                showToast('Błąd: ' + (result.error || result.message), 'error');
            }
        } catch { showToast('Błąd przy zapisywaniu Telegram', 'error'); }
    });

    if (chatIdInput) {
        chatIdInput.addEventListener('change', async (e) => {
            try {
                const result = await saveTelegramConfig(telegramToggle.checked, e.target.value);
                if (result.success) showToast('\u2705 Chat ID zapisany', 'success');
                else showToast('Błąd: ' + (result.error || result.message), 'error');
            } catch { showToast('Błąd przy zapisywaniu Chat ID', 'error'); }
        });
    }

    if (telegramTestBtn) {
        telegramTestBtn.addEventListener('click', async () => {
            const chatId = chatIdInput?.value || '';
            if (!chatId) { showToast('Podaj Chat ID', 'error'); return; }
            telegramTestBtn.disabled = true;
            telegramTestBtn.textContent = 'Testowanie...';
            try {
                const r = await fetch('backend.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'test-telegram-connection', chatId }),
                });
                const res = await r.json();
                if (res.success) {
                    showToast('\u2713 Telegram połączenie OK', 'success');
                    if (telegramStatus) telegramStatus.innerHTML = '<span style="color:#00be00;">\u2713 Połączenie aktywne</span>';
                } else {
                    showToast('\u2717 Błąd: ' + (res.error || res.message), 'error');
                    if (telegramStatus) telegramStatus.innerHTML = `<span style="color:#ff6464;">\u2717 Błąd: ${res.error || res.message}</span>`;
                }
            } catch (err) {
                showToast('Błąd testowania: ' + err.message, 'error');
                if (telegramStatus) telegramStatus.innerHTML = '<span style="color:#ff6464;">\u2717 Błąd testowania</span>';
            } finally {
                telegramTestBtn.disabled = false;
                telegramTestBtn.textContent = 'Testuj połączenie';
            }
        });
    }

    syncTelegramUI();
}
