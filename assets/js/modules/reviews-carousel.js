/** Горизонтальный список отзывов; без JS остаются все карточки и нативная прокрутка. */
export function initReviewsCarousel() {
    const root = document.querySelector('[data-reviews]');
    if (!root) return;
    const track = root.querySelector('[data-reviews-track]');
    const controls = root.querySelector('[data-reviews-controls]');
    const previous = root.querySelector('[data-reviews-previous]');
    const next = root.querySelector('[data-reviews-next]');
    const status = root.querySelector('[data-reviews-status]');
    if (!track || !controls || !previous || !next || !status) return;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function sync() {
        const overflow = track.scrollWidth > track.clientWidth + 2;
        controls.hidden = !overflow;
        previous.disabled = track.scrollLeft <= 2;
        next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
        const first = track.firstElementChild;
        if (!first) return;
        const step = first.getBoundingClientRect().width + (parseFloat(getComputedStyle(track).columnGap) || 0);
        const start = Math.round(track.scrollLeft / step) + 1;
        const visible = Math.max(1, Math.round(track.clientWidth / step));
        status.textContent = status.dataset.message.replace('{от}', start).replace('{до}', Math.min(track.children.length, start + visible - 1)).replace('{всего}', track.children.length);
    }

    function move(direction) {
        const step = track.firstElementChild.getBoundingClientRect().width + (parseFloat(getComputedStyle(track).columnGap) || 0);
        track.scrollBy({left: direction * step, behavior: reducedMotion.matches ? 'auto' : 'smooth'});
    }
    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    track.addEventListener('keydown', (event) => {
        if (event.target !== track || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') move(event.key === 'ArrowRight' ? 1 : -1);
        else track.scrollTo({left: event.key === 'Home' ? 0 : track.scrollWidth, behavior: reducedMotion.matches ? 'auto' : 'smooth'});
    });
    track.addEventListener('scroll', sync, {passive: true});
    window.addEventListener('resize', sync);
    sync();
}
