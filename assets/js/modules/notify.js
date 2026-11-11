/**
 * Уведомления: единственный способ показать пользователю всплывающее сообщение.
 * Использование в других модулях: import { notify } from './notify.js'; notify('Готово', 'success');
 * Типы: info, success, error. alert/confirm/prompt не используются (docs/specs/js.md).
 */

const NOTIFY_TIMEOUT = 4000;
const CONTAINER_ID = 'notify-container';

export function initNotify() {
	getOrCreateContainer();
}

export function notify(message, type = 'info') {
	const container = getOrCreateContainer();
	const toast = createToast(message, type);
	container.appendChild(toast);

	setTimeout(() => toast.remove(), NOTIFY_TIMEOUT);
}

function getOrCreateContainer() {
	let container = document.getElementById(CONTAINER_ID);
	if (!container) {
		container = document.createElement('div');
		container.id = CONTAINER_ID;
		container.className = 'notify-container';
		container.setAttribute('aria-live', 'polite');
		container.setAttribute('aria-atomic', 'false');
		document.body.appendChild(container);
	}
	return container;
}

function createToast(message, type) {
	const toast = document.createElement('div');
	toast.className = `notify notify--${type}`;
	toast.textContent = message;
	toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
	return toast;
}
