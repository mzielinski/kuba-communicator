// ============================================
// DWELL TIME — eye-tracking style interaction
// ============================================
import { state } from './state.js';

/**
 * Attach dwell-time behaviour to a button.
 * After the cursor dwells for state.dwellTimeMs ms the button is clicked automatically.
 */
export function initializeDwellTime(button) {
    if (!state.dwellEnabled) return;  // Skip dwell initialization if disabled

    const id = button.getAttribute('data-word-id');
    let timer = null;
    let triggered = false;

    const overlay = document.createElement('div');
    overlay.className = 'dwell-progress';
    overlay.style.opacity = '0';
    button.appendChild(overlay);

    button.addEventListener('pointerenter', () => {
        if (triggered) return;
        clearTimeout(timer);

        const start = Date.now();
        button.classList.add('dwell-active');

        const interval = setInterval(() => {
            if (!button.matches(':hover')) {
                clearInterval(interval);
                button.classList.remove('dwell-active');
                return;
            }
            overlay.style.opacity = String(Math.min((Date.now() - start) / state.dwellTimeMs, 0.7));
        }, 50);

        timer = setTimeout(() => {
            clearInterval(interval);
            button.classList.remove('dwell-active');
            if (!button.matches(':hover')) { overlay.style.opacity = '0'; return; }
            triggered = true;
            overlay.style.opacity = '0';
            console.log(`Dwell: ${id}`);
            button.click();
            setTimeout(() => { triggered = false; }, 500);
        }, state.dwellTimeMs);
    });

    button.addEventListener('pointerleave', () => {
        clearTimeout(timer); timer = null;
        button.classList.remove('dwell-active');
        overlay.style.opacity = '0';
    });

    button.addEventListener('click', () => {
        clearTimeout(timer); timer = null;
        button.classList.remove('dwell-active');
        overlay.style.opacity = '0';
        triggered = true;
        setTimeout(() => { triggered = false; }, 500);
    });
}
