// ============================================
// ABOUT MODAL — info about the creator, Kuba,
// contact details and FAQ
// ============================================

import { state } from './state.js';
import { translations } from './translations.js';
import { showToast } from './utils.js';
import { t } from './i18n.js';

const CHANGELOG_LIMIT = 5;

export function initializeAboutModal() {
    const btn   = document.getElementById('about-btn');
    const changelogBtn = document.getElementById('changelog-btn');
    const feedbackBtn = document.getElementById('feedback-btn');
    const modal = document.getElementById('about-modal');
    const close = document.getElementById('about-modal-close');

    if (!btn || !modal || !close) return;

    setupFaqAccordion(modal);
    setupFeedbackForm(modal);
    renderChangelogPanel(modal);
    syncFeedbackEmail(modal);

    document.addEventListener('app:translations-applied', () => {
        renderChangelogPanel(modal);
        syncFeedbackEmail(modal);
    });

    const openAboutModal = (panelId = 'about-panel-kuba') => {
        modal.classList.add('show');
        activateAboutPanel(panelId);
        if (panelId === 'about-panel-changelog') {
            renderChangelogPanel(modal);
        }
        if (panelId === 'about-panel-feedback') {
            syncFeedbackEmail(modal);
            focusFeedbackMessage(modal);
        }
    };

    // Open
    btn.addEventListener('click', () => {
        openAboutModal('about-panel-kuba');
    });

    if (changelogBtn) {
        changelogBtn.addEventListener('click', () => {
            openAboutModal('about-panel-changelog');
        });
    }

    if (feedbackBtn) {
        feedbackBtn.addEventListener('click', () => {
            openAboutModal('about-panel-feedback');
        });
    }

    // Close via × button
    close.addEventListener('click', () => modal.classList.remove('show'));

    // Close via backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.remove('show');
    });

    // Close via Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            modal.classList.remove('show');
        }
    });

    // Side-nav switching
    modal.querySelectorAll('.about-nav-btn').forEach(navBtn => {
        navBtn.addEventListener('click', () => {
            const target = navBtn.dataset.aboutPanel;
            activateAboutPanel(target);
            if (target === 'about-panel-changelog') {
                renderChangelogPanel(modal);
            }
            if (target === 'about-panel-feedback') {
                syncFeedbackEmail(modal);
                focusFeedbackMessage(modal);
            }
        });
    });
}

function setupFeedbackForm(modal) {
    const form = modal.querySelector('#about-feedback-form');
    if (!form) return;

    const typeSelect = form.querySelector('#feedback-type');
    const messageField = form.querySelector('#feedback-message');
    const submitBtn = form.querySelector('#feedback-submit-btn');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const type = (typeSelect?.value || '').trim();
        const message = (messageField?.value || '').trim();

        if (!type) {
            showToast(t('feedbackTypeRequired'), 'error');
            return;
        }

        if (!message) {
            showToast(t('feedbackMessageRequired'), 'error');
            messageField?.focus();
            return;
        }

        const originalLabel = submitBtn?.textContent || t('btnSendFeedback');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = t('feedbackSending');
        }

        try {
            const r = await fetch('api.php?action=send-feedback', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type, message, language: state.language }),
            });
            const data = await r.json();

            if (r.ok && data.success) {
                showToast(data.message || t('feedbackSent'), 'success');
                const selectedType = type;
                form.reset();
                if (typeSelect) typeSelect.value = selectedType;
                if (messageField) messageField.value = '';
            } else {
                showToast(data.message || t('feedbackError'), 'error');
            }
        } catch (err) {
            showToast(t('feedbackError'), 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalLabel;
            }
        }
    });
}

function syncFeedbackEmail(modal) {
    const emailEl = modal.querySelector('[data-feedback-email]');
    if (emailEl) {
        emailEl.textContent = state.userEmail || '—';
    }
}

function focusFeedbackMessage(modal) {
    const messageField = modal.querySelector('#feedback-message');
    if (messageField) {
        setTimeout(() => messageField.focus(), 0);
    }
}

function renderChangelogPanel(modal) {
    const list = modal.querySelector('#about-changelog-list');
    if (!list) return;

    const lang = state.language === 'en' ? 'en' : 'pl';
    const dict = translations[lang] || translations.pl;
    const entries = Array.isArray(dict.changelogEntries) ? dict.changelogEntries.slice(0, CHANGELOG_LIMIT) : [];

    if (!entries.length) {
        list.innerHTML = `<p class="about-changelog-empty">${escapeHtml(dict.aboutChangelogEmpty || '')}</p>`;
        return;
    }

    list.innerHTML = entries.map((entry) => {
        const date = formatChangelogDate(entry.date, lang);
        const description = escapeHtml(entry.description || '');

        return `
            <article class="about-changelog-item">
                <div class="about-changelog-date">${escapeHtml(date)}</div>
                <div class="about-changelog-description">${description}</div>
            </article>
        `;
    }).join('');
}

function formatChangelogDate(value, lang) {
    if (!value) return '';

    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return String(value);

    return new Intl.DateTimeFormat(lang === 'en' ? 'en-GB' : 'pl-PL', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(date);
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function setupFaqAccordion(modal) {
    const faqList = modal.querySelector('.about-faq-list');
    if (!faqList) return;

    const faqQuestions = faqList.querySelectorAll('.about-faq-q');

    faqQuestions.forEach((question, index) => {
        const item = question.closest('.about-faq-item');
        const answer = item ? item.querySelector('.about-faq-a') : null;

        question.setAttribute('role', 'button');
        question.setAttribute('tabindex', '0');
        question.setAttribute('aria-expanded', 'false');

        if (answer && !answer.id) {
            answer.id = `about-faq-answer-${index + 1}`;
        }

        if (answer) {
            question.setAttribute('aria-controls', answer.id);
        }

        if (item) {
            item.classList.remove('is-open');
        }
    });

    faqList.addEventListener('click', (e) => {
        const question = e.target.closest('.about-faq-q');
        if (!question || !faqList.contains(question)) return;
        toggleFaqItem(question);
    });

    faqList.addEventListener('keydown', (e) => {
        const question = e.target.closest('.about-faq-q');
        if (!question || !faqList.contains(question)) return;

        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleFaqItem(question);
        }
    });
}

function toggleFaqItem(question) {
    const item = question.closest('.about-faq-item');
    if (!item) return;

    const isOpen = item.classList.toggle('is-open');
    question.setAttribute('aria-expanded', String(isOpen));
}

function activateAboutPanel(panelId) {
    const modal = document.getElementById('about-modal');
    if (!modal) return;

    modal.querySelectorAll('.about-nav-btn').forEach(b => b.classList.remove('active'));
    modal.querySelectorAll('.about-panel').forEach(p => p.classList.remove('active'));

    const activeBtn = modal.querySelector(`[data-about-panel="${panelId}"]`);
    const activePanel = document.getElementById(panelId);
    if (activeBtn)  activeBtn.classList.add('active');
    if (activePanel) activePanel.classList.add('active');
}

