// ============================================
// GLOBAL STATE
// ============================================

export const state = {
    categories: {},
    isSpeaking: false,
    editingWord: null,       // { category, index }
    dwellTimeMs: 2000,
    dwellEnabled: true,
    dwellTimers: {},
    expandedCategory: null,
    selectedWordsCategory: null,
};
