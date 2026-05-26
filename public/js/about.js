// ============================================
// ABOUT MODAL — info about the creator, Kuba,
// contact details and FAQ
// ============================================

export function initializeAboutModal() {
    const btn   = document.getElementById('about-btn');
    const modal = document.getElementById('about-modal');
    const close = document.getElementById('about-modal-close');

    if (!btn || !modal || !close) return;

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

