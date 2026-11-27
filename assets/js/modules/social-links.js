import { notify } from './notify.js';

/** Заглушки не ведут на вымышленные аккаунты. */
export function initSocialLinks() {
    const placeholders = document.querySelectorAll('[data-social-placeholder]');
    if (!placeholders.length) return;
    placeholders.forEach((link) => link.addEventListener('click', (event) => {
        event.preventDefault();
        notify(link.dataset.message, 'info');
    }));
}
