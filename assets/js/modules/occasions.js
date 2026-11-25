/** Выбор повода: все панели остаются доступны без JavaScript. */
export function initOccasions() {
    const root = document.querySelector('[data-occasions]');
    if (!root) return;
    const choices = root.querySelector('[data-occasion-tabs]');
    const tabs = [...root.querySelectorAll('[data-occasion-tab]')];
    const panels = [...root.querySelectorAll('[data-occasion-panel]')];
    if (!choices || !tabs.length || tabs.length !== panels.length) return;
    choices.setAttribute('role', 'tablist');
    tabs.forEach((tab, index) => {
        tab.setAttribute('role', 'tab');
        panels[index].setAttribute('role', 'tabpanel');
        panels[index].setAttribute('aria-labelledby', tab.id);
        panels[index].tabIndex = 0;
        tab.addEventListener('click', () => select(index));
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            select(next);
            tabs[next].focus();
        });
    });
    function select(index) {
        tabs.forEach((tab, current) => {
            tab.setAttribute('aria-selected', current === index ? 'true' : 'false');
            tab.tabIndex = current === index ? 0 : -1;
            panels[current].hidden = current !== index;
        });
    }
    select(0);
    choices.hidden = false;
}
