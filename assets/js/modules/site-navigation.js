/** Доступная мобильная навигация. Без JS ссылки остаются видимыми. */
export function initSiteNavigation() {
    const header = document.querySelector('[data-site-header]');
    if (!header) return;
    const navigation = header.querySelector('[data-navigation]');
    const toggle = header.querySelector('[data-navigation-toggle]');
    if (!navigation || !toggle) return;
    const desktop = window.matchMedia('(min-width: 960px)');

    function setOpen(open) {
        navigation.hidden = !open && !desktop.matches;
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute(
            'aria-label',
            open ? toggle.dataset.labelClose : toggle.dataset.labelOpen,
        );
    }

    function syncViewport() {
        const focusWasInside = navigation.contains(document.activeElement);
        const focusWasToggle = document.activeElement === toggle;
        toggle.hidden = desktop.matches;
        setOpen(desktop.matches);
        if (!desktop.matches && focusWasInside) toggle.focus();
        if (desktop.matches && focusWasToggle) navigation.querySelector('a').focus();
    }

    toggle.addEventListener('click', () =>
        setOpen(toggle.getAttribute('aria-expanded') !== 'true'),
    );
    navigation.addEventListener('click', (event) => {
        if (!desktop.matches && event.target.closest('a')) setOpen(false);
    });
    header.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape' &&
            !desktop.matches &&
            toggle.getAttribute('aria-expanded') === 'true'
        ) {
            setOpen(false);
            toggle.focus();
        }
    });
    desktop.addEventListener('change', syncViewport);
    syncViewport();
}
