// ============================================
// GLOBAL STATE
// ============================================

export const state = {
    categories: {},
    isSpeaking: false,
    editingWord: null,
    dwellTimeMs: 2000,
    dwellEnabled: true,
    dwellTimers: {},
    expandedCategory: null,
    selectedWordsCategory: null,
    darkModeEnabled: false,
    alarmDuration: 6,
    language: 'pl',
    recentlyClickedMessages: [] // Array of {text, category, timestamp}
};
