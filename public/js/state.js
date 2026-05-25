// ============================================
// GLOBAL STATE
// ============================================

export const state = {
    categories: {},
    globalWords: [],
    isSpeaking: false,
    editingWord: null,
    dwellTimeMs: 2000,
    dwellEnabled: true,
    dwellTimers: {},
    expandedCategory: null,
    selectedWordsCategory: null,
    darkModeEnabled: true,
    alarmDuration: 6,
    language: 'pl',
    recentlyClickedMessages: [],
    // Alarm button in category
    alarmButtonEnabled: false,
    alarmButtonCategory: '',
    // Virtual keyboard button in category
    keyboardEnabled: false,
    keyboardCategory: '',
    // User / session
    userEmail: '',
    userRole: 'USER', // ADMIN | USER | DEMO
};
