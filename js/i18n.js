// ============================================
// INTERNATIONALISATION (i18n)
// ============================================
import { state } from './state.js';

const translations = {
    pl: {
        // Page
        pageTitle: 'Kuba # Urządzenie Komunikacyjne',
        appHeading: 'Kuba # Urządzenie Komunikacyjne',

        // Status bar
        statusReady: 'Gotowe',
        statusSpeaking: 'Mówię...',
        statusError: 'Błąd',

        // Recent messages
        recentMessagesLabel: 'Ostatnio kliknięte:',

        // Header buttons
        manageWordsBtnTitle: 'Zarządzaj słowami',
        logoutBtnTitle: 'Wyloguj się',

        // Modal header
        modalTitle: 'Zarządzaj słowami',

        // Tabs
        tabCategories: 'Zarządzaj kategoriami',
        tabWords: 'Zarządzaj Słowami',
        tabGlobalWords: 'Globalne Słowa',
        tabSettings: 'Ustawienia',

        // Categories tab
        labelManageCategories: 'Zarządzaj kategoriami:',
        emptyCategories: 'Brak kategorii',
        headingAddCategory: '➕ Dodaj nową kategorię',
        placeholderCategoryName: 'Nazwa nowej kategorii...',
        btnAdd: '➕ Dodaj',
        hintClickCategory: 'Kliknij na kategorię aby edytować lub usunąć',

        // Global Words tab (pl)
        globalWordsHint: 'Globalne słowa są automatycznie dodawane do kategorii. Każde słowo ma własny zakres wyświetlania — możesz ustawić czy ma być widoczne tylko w rozwijalnych kategoriach, czy we wszystkich.',
        emptyGlobalWords: 'Brak globalnych słów. Dodaj pierwsze!',
        headingAddGlobalWord: '➕ Dodaj globalne słowo',
        globalWordAdded: '"{{item}}" dodane jako globalne słowo!',
        globalWordDeleted: '"{{item}}" usunięte z globalnych słów!',
        globalWordUpdated: '"{{item}}" zaktualizowane!',
        globalWordMoveUp: 'Globalne słowo przesunięte w górę',
        globalWordMoveDown: 'Globalne słowo przesunięte w dół',
        labelWordScope: 'Zakres:',
        scopeExpandOnly: 'Tylko rozwijalne kategorie',
        scopeAll: 'Wszystkie kategorie',
        globalWordsSaved: '✅ Globalne słowa zapisane!',
        errorSavingGlobalWords: 'Błąd przy zapisywaniu globalnych słów',

        // Words tab
        labelSelectCategory: 'Wybierz kategorię:',
        optionSelectCategory: '-- Wybierz kategorię --',
        emptySelectCategory: 'Wybierz kategorię aby zobaczyć słowa',
        emptyWordsInCategory: 'Brak słów w tej kategorii.',
        headingAddWord: '➕ Dodaj nowe słowo',
        placeholderWordText: 'Tekst słowa...',

        // Settings – Alarm
        headingAlarmSettings: '🔔 Ustawienia alarmu',
        labelAlarmType: 'Rodzaj dźwięku alarmu:',
        btnTestAlarm: '🔊 Testuj',
        hintAlarmType: 'Wybierz rodzaj dźwięku dostosowany do potrzeb słuchowych użytkownika.',
        labelAlarmDevice: 'Urządzenie dla alarmu:',
        optionDefaultDevice: 'Domyślne',
        hintAlarmDevice: 'Alarm zawsze będzie grał na wybranym urządzeniu, niezależnie od ustawień systemowych.',
        labelAlarmDuration: 'Długość alarmu:',
        hintAlarmDuration: 'Ustaw czas trwania alarmu od 1 do 10 sekund.',

        // Alarm profiles
        alarmProfileHigh: '🔔 Wysokie tony (900 / 750 Hz)',
        alarmProfileMid: '🎵 Średnie tony (500 / 400 Hz)',
        alarmProfileLow: '🔊 Niskie tony (220 / 160 Hz)',
        alarmProfileSiren: '🚨 Syrena (150 → 800 Hz sweep)',

        // Alarm toasts
        alarmToast: '🚨 ALARM - Osoba potrzebuje wsparcia!',
        alarmOn: 'Alarm na: {{item}}',
        alarmStopped: 'Alarm wyłączony',
        alarmGenerateError: 'Błąd generowania alarmu',
        alarmPlayError: 'Błąd odtwarzania alarmu',
        alarmTestGenerateError: 'Błąd generowania dźwięku testowego',
        alarmTestPlayError: 'Błąd odtwarzania dźwięku',
        alarmTestError: 'Błąd podczas testowania alarmu',
        alarmTypeSaved: '🔔 Rodzaj alarmu: {{item}}',
        alarmWillBe: 'Alarm będzie na: {{item}}',
        alarmDefaultDevice: 'Alarm na domyślnym urządzeniu',
        noAudioDevices: '❌ Brak dostępnych urządzeń audio',
        oneAudioDevice: '📱 Jedno urządzenie audio dostępne',
        audioDevicesCount: '✅ {{count}} urządzeń audio dostępnych',
        browserNoAudio: 'Twoja przeglądarka nie obsługuje wyboru urządzenia audio',
        micPermission: '💡 Przyznaj dostęp do mikrofonu aby zobaczyć wszystkie urządzenia audio',
        alarmTesting: '🔄 Testowanie...',

        // Settings – Dwell
        headingDwell: '👁️ Patrzenie na przycisk (Dwell Time)',
        labelDwellEnabled: 'Włącz patrzenie na przycisk (Dwell Time)',
        hintDwellEnabled: 'Gdy włączysz, przycisk będzie automatycznie aktywowany po patrzeniu na niego przez wskazany czas.',
        labelDwellTime: 'Czas patrzenia na przycisk (Dwell Time):',
        hintDwellTime: 'Przycisk zostanie automatycznie aktywowany po patrzeniu na niego przez wskazany czas. Gdy odsuniesz kursor licznik zresetuje się.',

        // Settings – Dark mode
        headingDarkMode: '🌙 Tryb ciemny',
        labelDarkMode: 'Włącz tryb ciemny',
        hintDarkMode: 'Zmień tło na ciemne dla wygody w słabym oświetleniu.',

        // Settings – Telegram
        labelTelegramEnabled: 'Włącz wysyłanie wiadomości na Telegram',
        hintTelegramEnabled: 'Gdy włączysz, wiadomości będą wysyłane na wybrany kanał Telegram.',
        labelTelegramRecipient: 'Wybierz odbiorcę:',
        optionNoRecipients: '-- Brak skonfigurowanych odbiorców --',
        headingAddRecipient: '➕ Dodaj nowego odbiorcę',
        placeholderTelegramName: 'Nazwa (np. John)',
        hintTelegramChatId: 'Znajdziesz swój Chat ID wpisując @userinfobot w Telegramie',
        emptyRecipients: 'Brak skonfigurowanych odbiorców',
        btnTestConnection: 'Testuj połączenie',
        btnTesting: 'Testowanie...',

        // Settings – Language
        headingLanguage: '🌐 Język',
        labelLanguage: 'Wybierz język:',
        optionPolish: 'Polski',
        optionEnglish: 'English',

        // Modal footer
        btnSaveChanges: '💾 Zapisz zmiany',
        btnCancelChanges: '❌ Anuluj (odrzuć zmiany)',

        // App init
        appReady: 'Aplikacja gotowa!',
        errorLoadingWords: 'Nie udało się załadować słów. Sprawdź plik words.json',

        // Words list actions
        wordMoveUp: 'Słowo przesunięte w górę',
        wordMoveDown: 'Słowo przesunięte w dół',
        wordDeleted: '"{{item}}" usunięte!',
        wordUpdated: '"{{item}}" zaktualizowane!',
        wordAdded: '"{{item}}" dodane!',
        errorSelectCategory: 'Proszę wybrać kategorię',
        errorEnterWordText: 'Proszę wprowadzić tekst słowa',
        errorTextEmpty: 'Tekst nie może być pusty',
        btnMoveUp: 'Przesuń wyżej',
        btnMoveDown: 'Przesuń niżej',
        wordSizeDefault: 'Rozmiar: domyślny',
        wordSize: 'Rozmiar: {{item}}',

        // Category actions
        catMoveUpAlready: 'Kategoria jest już na górze',
        catMoveDownAlready: 'Kategoria jest już na dole',
        catMoveUp: 'Kategoria przesunięta w górę',
        catMoveDown: 'Kategoria przesunięta w dół',
        catAdded: '✅ Kategoria "{{item}}" dodana!',
        catDeleted: '✅ Kategoria "{{item}}" usunięta!',
        catUpdated: '✅ Kategoria "{{item}}" zaktualizowana!',
        errorCatNameEmpty: 'Wpisz nazwę kategorii',
        errorCatExists: 'Kategoria już istnieje',
        errorCatNameEmptyEdit: 'Nazwa nie może być pusta',
        errorCatNameExistsEdit: 'Kategoria z taką nazwą już istnieje',
        catWordCount: '{{count}} słów | Rozmiar: {{size}}',

        // Save / cancel
        allChangesSaved: '✅ Wszystkie zmiany zapisane!',
        errorSavingChanges: 'Błąd przy zapisywaniu zmian',
        changesDiscarded: '❌ Zmiany odrzucone, załadowano dane z pliku',

        // Confirm dialogs
        confirmDiscardTitle: '⚠️ Odrzuć zmiany',
        confirmDiscardMsg: 'Czy na pewno chcesz zamknąć bez zapisywania? Wszystkie zmiany zostaną stracone.',
        confirmDiscardOk: 'Odrzuć',
        confirmDiscardCancel: 'Kontynuuj edycję',
        confirmDeleteWordTitle: '🗑️ Usuń słowo',
        confirmDeleteWordMsg: 'Czy usunąć "{{item}}"?',
        confirmDeleteOk: 'Usuń',
        confirmDeleteCancel: 'Anuluj',
        confirmDeleteCatTitle: '🗑️ Usuń kategorię',
        confirmDeleteCatMsg: 'Usunąć kategorię "{{item}}" ze wszystkimi słowami?',
        confirmDeleteRecipientTitle: '🗑️ Usuń odbiorcę',
        confirmDeleteRecipientMsg: 'Czy na pewno chcesz usunąć tego odbiorcę?',
        confirmDefault: 'Potwierdź',
        cancelDefault: 'Anuluj',

        // Edit word dialog
        editWordTitle: '✏️ Edytuj słowo',
        labelWordText: 'Tekst słowa:',
        labelColor: 'Kolor:',
        labelFontSize: 'Rozmiar fontu (px):',
        placeholderFontSize: 'domyślny (30px)',
        btnSave: 'Zapisz',
        btnCancel: 'Anuluj',

        // Edit category dialog
        editCatTitle: 'Edytuj kategorię: {{item}}',
        labelCatName: 'Nazwa kategorii:',
        labelDisplaySize: 'Rozmiar wyświetlania:',
        sizeSmall: 'Mały (small)',
        sizeMedium: 'Średni (medium)',
        sizeLarge: 'Duży (large)',
        labelExpandable: 'Rozwijalna kategoria (otwiera osobny widok)',

        // Renderer
        noCategories: 'Brak kategorii',
        backButton: '🔙 Powrót',

        // Speech
        speechError: 'Błąd odczytu tekstu',

        // Clipboard
        copied: 'Skopiowano: {{item}}',
        copyFailed: 'Nie udało się skopiować',

        // API errors
        errorLoadWords: 'Błąd ładowania słów',
        errorSaveWords: 'Nie udało się zapisać zmian: {{item}}',
        errorSaveWordsGeneral: 'Błąd przy zapisywaniu zmian: {{item}}',

        // Telegram
        telegramChatIdNotConfigured: '⚠️ Chat ID nie skonfigurowany',
        telegramSent: '✓ Wysłane na Telegram',
        telegramWarning: '⚠️ Telegram: {{item}}',
        telegramCannotSend: '⚠️ Nie mogę wysłać na Telegram, ale komunikacja działa',
        telegramEnabled: '✅ Telegram włączony',
        telegramDisabled: '✅ Telegram wyłączony',
        telegramRecipientUpdated: '✅ Odbiorca zaktualizowany',
        telegramRecipientAdded: '✅ Odbiorca dodany',
        telegramRecipientDeleted: '✅ Odbiorca usunięty',
        telegramRecipientSelected: '✅ Odbiorca wybrany',
        telegramConnectionOk: '✅ Telegram połączenie OK',
        telegramConnectionActive: '✓ Połączenie aktywne',
        errorSelectRecipient: '❌ Wybierz odbiorcę',
        errorEnterRecipientName: '❌ Podaj nazwę odbiorcy',
        errorEnterChatId: '❌ Podaj Chat ID',
        errorNameEmpty: '❌ Nazwa nie może być pusta',
        errorRecipientUpdate: '❌ Błąd przy aktualizacji odbiorcy',
        errorRecipientDelete: '❌ Błąd przy usuwaniu odbiorcy',
        errorRecipientAdd: '❌ Błąd przy dodawaniu odbiorcy',
        errorTelegramSave: '❌ Błąd przy zapisywaniu Telegram',
        errorTelegramChat: '❌ Błąd: {{item}}',
        errorTelegramTest: '❌ Błąd testowania: {{item}}',
        errorTelegramTestConn: '✗ Błąd: {{item}}',
        errorTelegramTestGeneral: '✗ Błąd testowania',

        // Auth
        loggedOut: 'Wylogowano pomyślnie',
        logoutError: 'Błąd podczas wylogowania',
        confirmLogoutTitle: '⏻ Wyloguj się',
        confirmLogoutMsg: 'Czy naprawdę chcesz się wylogować?',
        confirmLogoutOk: 'Wyloguj',
    },

    en: {
        // Page
        pageTitle: 'Kuba # Communication Device',
        appHeading: 'Kuba # Communication Device',

        // Status bar
        statusReady: 'Ready',
        statusSpeaking: 'Speaking...',
        statusError: 'Error',

        // Recent messages
        recentMessagesLabel: 'Recently clicked:',

        // Header buttons
        manageWordsBtnTitle: 'Manage words',
        logoutBtnTitle: 'Log out',

        // Modal header
        modalTitle: 'Manage words',

        // Tabs
        tabCategories: 'Manage categories',
        tabWords: 'Manage Words',
        tabGlobalWords: 'Global Words',
        tabSettings: 'Settings',

        // Categories tab
        labelManageCategories: 'Manage categories:',
        emptyCategories: 'No categories',
        headingAddCategory: '➕ Add new category',
        placeholderCategoryName: 'New category name...',
        btnAdd: '➕ Add',
        hintClickCategory: 'Click on a category to edit or delete',

        // Global Words tab (en)
        globalWordsHint: 'Global words are automatically added to categories. Each word has its own scope — you can set whether it appears only in expandable categories or in all categories.',
        emptyGlobalWords: 'No global words yet. Add the first one!',
        headingAddGlobalWord: '➕ Add global word',
        globalWordAdded: '"{{item}}" added as a global word!',
        globalWordDeleted: '"{{item}}" removed from global words!',
        globalWordUpdated: '"{{item}}" updated!',
        globalWordMoveUp: 'Global word moved up',
        globalWordMoveDown: 'Global word moved down',
        labelWordScope: 'Scope:',
        scopeExpandOnly: 'Expandable categories only',
        scopeAll: 'All categories',
        globalWordsSaved: '✅ Global words saved!',
        errorSavingGlobalWords: 'Error saving global words',

        // Words tab
        labelSelectCategory: 'Select category:',
        optionSelectCategory: '-- Select category --',
        emptySelectCategory: 'Select a category to see words',
        emptyWordsInCategory: 'No words in this category.',
        headingAddWord: '➕ Add new word',
        placeholderWordText: 'Word text...',

        // Settings – Alarm
        headingAlarmSettings: '🔔 Alarm settings',
        labelAlarmType: 'Alarm sound type:',
        btnTestAlarm: '🔊 Test',
        hintAlarmType: "Select a sound type tailored to the user's hearing needs.",
        labelAlarmDevice: 'Alarm device:',
        optionDefaultDevice: 'Default',
        hintAlarmDevice: 'The alarm will always play on the selected device, regardless of system settings.',
        labelAlarmDuration: 'Alarm duration:',
        hintAlarmDuration: 'Set the alarm duration from 1 to 10 seconds.',

        // Alarm profiles
        alarmProfileHigh: '🔔 High tones (900 / 750 Hz)',
        alarmProfileMid: '🎵 Mid tones (500 / 400 Hz)',
        alarmProfileLow: '🔊 Low tones (220 / 160 Hz)',
        alarmProfileSiren: '🚨 Siren (150 → 800 Hz sweep)',

        // Alarm toasts
        alarmToast: '🚨 ALARM - Person needs support!',
        alarmOn: 'Alarm on: {{item}}',
        alarmStopped: 'Alarm stopped',
        alarmGenerateError: 'Error generating alarm',
        alarmPlayError: 'Error playing alarm',
        alarmTestGenerateError: 'Error generating test sound',
        alarmTestPlayError: 'Error playing sound',
        alarmTestError: 'Error during alarm test',
        alarmTypeSaved: '🔔 Alarm type: {{item}}',
        alarmWillBe: 'Alarm will be on: {{item}}',
        alarmDefaultDevice: 'Alarm on default device',
        noAudioDevices: '❌ No audio devices available',
        oneAudioDevice: '📱 One audio device available',
        audioDevicesCount: '✅ {{count}} audio devices available',
        browserNoAudio: 'Your browser does not support audio device selection',
        micPermission: '💡 Grant microphone access to see all audio devices',
        alarmTesting: '🔄 Testing...',

        // Settings – Dwell
        headingDwell: '👁️ Button gaze (Dwell Time)',
        labelDwellEnabled: 'Enable button gaze (Dwell Time)',
        hintDwellEnabled: 'When enabled, the button will be automatically activated after gazing at it for the specified time.',
        labelDwellTime: 'Button gaze time (Dwell Time):',
        hintDwellTime: 'The button will be automatically activated after gazing at it for the specified time. Moving the cursor away will reset the counter.',

        // Settings – Dark mode
        headingDarkMode: '🌙 Dark mode',
        labelDarkMode: 'Enable dark mode',
        hintDarkMode: 'Change the background to dark for comfort in low light.',

        // Settings – Telegram
        labelTelegramEnabled: 'Enable sending messages to Telegram',
        hintTelegramEnabled: 'When enabled, messages will be sent to the selected Telegram channel.',
        labelTelegramRecipient: 'Select recipient:',
        optionNoRecipients: '-- No configured recipients --',
        headingAddRecipient: '➕ Add new recipient',
        placeholderTelegramName: 'Name (e.g. John)',
        hintTelegramChatId: 'Find your Chat ID by typing @userinfobot in Telegram',
        emptyRecipients: 'No configured recipients',
        btnTestConnection: 'Test connection',
        btnTesting: 'Testing...',

        // Settings – Language
        headingLanguage: '🌐 Language',
        labelLanguage: 'Select language:',
        optionPolish: 'Polski',
        optionEnglish: 'English',

        // Modal footer
        btnSaveChanges: '💾 Save changes',
        btnCancelChanges: '❌ Cancel (discard changes)',

        // App init
        appReady: 'App ready!',
        errorLoadingWords: 'Failed to load words. Check words.json file.',

        // Words list actions
        wordMoveUp: 'Word moved up',
        wordMoveDown: 'Word moved down',
        wordDeleted: '"{{item}}" deleted!',
        wordUpdated: '"{{item}}" updated!',
        wordAdded: '"{{item}}" added!',
        errorSelectCategory: 'Please select a category',
        errorEnterWordText: 'Please enter word text',
        errorTextEmpty: 'Text cannot be empty',
        btnMoveUp: 'Move up',
        btnMoveDown: 'Move down',
        wordSizeDefault: 'Size: default',
        wordSize: 'Size: {{item}}',

        // Category actions
        catMoveUpAlready: 'Category is already at the top',
        catMoveDownAlready: 'Category is already at the bottom',
        catMoveUp: 'Category moved up',
        catMoveDown: 'Category moved down',
        catAdded: '✅ Category "{{item}}" added!',
        catDeleted: '✅ Category "{{item}}" deleted!',
        catUpdated: '✅ Category "{{item}}" updated!',
        errorCatNameEmpty: 'Enter category name',
        errorCatExists: 'Category already exists',
        errorCatNameEmptyEdit: 'Name cannot be empty',
        errorCatNameExistsEdit: 'A category with this name already exists',
        catWordCount: '{{count}} words | Size: {{size}}',

        // Save / cancel
        allChangesSaved: '✅ All changes saved!',
        errorSavingChanges: 'Error saving changes',
        changesDiscarded: '❌ Changes discarded, data reloaded from file',

        // Confirm dialogs
        confirmDiscardTitle: '⚠️ Discard changes',
        confirmDiscardMsg: 'Are you sure you want to close without saving? All changes will be lost.',
        confirmDiscardOk: 'Discard',
        confirmDiscardCancel: 'Continue editing',
        confirmDeleteWordTitle: '🗑️ Delete word',
        confirmDeleteWordMsg: 'Delete "{{item}}"?',
        confirmDeleteOk: 'Delete',
        confirmDeleteCancel: 'Cancel',
        confirmDeleteCatTitle: '🗑️ Delete category',
        confirmDeleteCatMsg: 'Delete category "{{item}}" with all words?',
        confirmDeleteRecipientTitle: '🗑️ Delete recipient',
        confirmDeleteRecipientMsg: 'Are you sure you want to delete this recipient?',
        confirmDefault: 'Confirm',
        cancelDefault: 'Cancel',

        // Edit word dialog
        editWordTitle: '✏️ Edit word',
        labelWordText: 'Word text:',
        labelColor: 'Color:',
        labelFontSize: 'Font size (px):',
        placeholderFontSize: 'default (30px)',
        btnSave: 'Save',
        btnCancel: 'Cancel',

        // Edit category dialog
        editCatTitle: 'Edit category: {{item}}',
        labelCatName: 'Category name:',
        labelDisplaySize: 'Display size:',
        sizeSmall: 'Small',
        sizeMedium: 'Medium',
        sizeLarge: 'Large',
        labelExpandable: 'Expandable category (opens separate view)',

        // Renderer
        noCategories: 'No categories',
        backButton: '🔙 Back',

        // Speech
        speechError: 'Speech error',

        // Clipboard
        copied: 'Copied: {{item}}',
        copyFailed: 'Failed to copy',

        // API errors
        errorLoadWords: 'Error loading words',
        errorSaveWords: 'Failed to save changes: {{item}}',
        errorSaveWordsGeneral: 'Error saving changes: {{item}}',

        // Telegram
        telegramChatIdNotConfigured: '⚠️ Chat ID not configured',
        telegramSent: '✓ Sent to Telegram',
        telegramWarning: '⚠️ Telegram: {{item}}',
        telegramCannotSend: '⚠️ Cannot send to Telegram, but communication works',
        telegramEnabled: '✅ Telegram enabled',
        telegramDisabled: '✅ Telegram disabled',
        telegramRecipientUpdated: '✅ Recipient updated',
        telegramRecipientAdded: '✅ Recipient added',
        telegramRecipientDeleted: '✅ Recipient deleted',
        telegramRecipientSelected: '✅ Recipient selected',
        telegramConnectionOk: '✅ Telegram connection OK',
        telegramConnectionActive: '✓ Connection active',
        errorSelectRecipient: '❌ Select a recipient',
        errorEnterRecipientName: '❌ Enter recipient name',
        errorEnterChatId: '❌ Enter Chat ID',
        errorNameEmpty: '❌ Name cannot be empty',
        errorRecipientUpdate: '❌ Error updating recipient',
        errorRecipientDelete: '❌ Error deleting recipient',
        errorRecipientAdd: '❌ Error adding recipient',
        errorTelegramSave: '❌ Error saving Telegram',
        errorTelegramChat: '❌ Error: {{item}}',
        errorTelegramTest: '❌ Test error: {{item}}',
        errorTelegramTestConn: '✗ Error: {{item}}',
        errorTelegramTestGeneral: '✗ Test error',

        // Auth
        loggedOut: 'Logged out successfully',
        logoutError: 'Error during logout',
        confirmLogoutTitle: '⏻ Log out',
        confirmLogoutMsg: 'Are you sure you want to log out?',
        confirmLogoutOk: 'Log out',
    },
};

/**
 * Translate a key with optional variable substitution.
 * Variables use {{name}} syntax.
 * @param {string} key
 * @param {Object} [vars]
 * @returns {string}
 */
export function t(key, vars = {}) {
    const lang = state.language || 'pl';
    const dict = translations[lang] || translations['pl'];
    let str = dict[key] ?? translations['pl'][key] ?? key;
    Object.entries(vars).forEach(([k, v]) => {
        str = str.replaceAll(`{{${k}}}`, String(v));
    });
    return str;
}

/**
 * Apply all data-i18n translations to the live DOM.
 * Call this on startup and whenever the language changes.
 */
export function applyTranslations() {
    document.documentElement.lang = state.language === 'en' ? 'en' : 'pl';
    document.title = t('pageTitle');

    document.querySelectorAll('[data-i18n]').forEach(el => {
        el.textContent = t(el.getAttribute('data-i18n'));
    });
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
        el.title = t(el.getAttribute('data-i18n-title'));
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        el.placeholder = t(el.getAttribute('data-i18n-placeholder'));
    });
}

