/** Нативная модальная корзина справа, без изменения расчёта заказа. */
export function initOrderDrawer() {
    const drawer = document.querySelector('[data-order-drawer]');
    const triggers = document.querySelectorAll('[data-order-open]');
    if (!drawer || !triggers.length || typeof drawer.showModal !== 'function') return;
    const closeButton = drawer.querySelector('[data-order-close]');
    const menuHeading = document.querySelector('[data-order-menu-focus]');
    if (!closeButton) return;
    let opener = null;
    let goingToMenu = false;

    function openDrawer(event) {
        if (drawer.open) return;
        opener = event.currentTarget;
        goingToMenu = false;
        drawer.showModal();
        document.documentElement.classList.add('is-cart-open');
    }

    function closeDrawer() {
        drawer.close();
    }

    triggers.forEach((trigger) => {
        trigger.hidden = false;
        trigger.addEventListener('click', openDrawer);
    });
    closeButton.addEventListener('click', closeDrawer);
    drawer.addEventListener('click', (event) => {
        if (event.target !== drawer) return;
        const bounds = drawer.getBoundingClientRect();
        const outside =
            event.clientX < bounds.left ||
            event.clientX > bounds.right ||
            event.clientY < bounds.top ||
            event.clientY > bounds.bottom;
        if (outside) closeDrawer();
    });
    drawer.querySelectorAll('[data-order-dismiss]').forEach((link) => {
        link.addEventListener('click', () => {
            goingToMenu = true;
            closeDrawer();
        });
    });
    drawer.addEventListener('close', () => {
        document.documentElement.classList.remove('is-cart-open');
        if (goingToMenu && menuHeading) {
            menuHeading.focus({ preventScroll: true });
        } else if (opener?.isConnected) {
            opener.focus({ preventScroll: true });
        }
        goingToMenu = false;
    });
}
