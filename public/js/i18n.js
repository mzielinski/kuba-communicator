// ============================================
// INTERNATIONALISATION (i18n)
// ============================================
import { state } from './state.js';
import { translations } from './translations.js';

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

    document.dispatchEvent(new CustomEvent('app:translations-applied', {
        detail: { language: state.language }
    }));
}
