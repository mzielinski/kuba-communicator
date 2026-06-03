// ============================================
// ABOUT MODAL — info about the creator, Kuba,
// contact details and FAQ
// ============================================

export function initializeAboutModal() {
    const btn   = document.getElementById('about-btn');
    const modal = document.getElementById('about-modal');
    const close = document.getElementById('about-modal-close');

    if (!btn || !modal || !close) return;

    setupFaqAccordion(modal);

    // Open
    btn.addEventListener('click', () => {
        modal.classList.add('show');
        // Activate first nav item by default each time it opens
        activateAboutPanel('about-panel-kuba');
    });

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
        });
    });
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

