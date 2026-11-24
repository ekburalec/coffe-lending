/** Уменьшение движения; пауза при наведении и фокусе реализована в CSS. */
export function initAnnouncementBar() {
    const bar = document.querySelector('[data-announcement-bar]');
    if (!bar) return;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function syncMotion() {
        bar.classList.toggle('is-running', !reducedMotion.matches);
    }

    reducedMotion.addEventListener('change', syncMotion);
    syncMotion();
}
