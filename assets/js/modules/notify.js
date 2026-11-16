let notification;
let hideTimer;

export function initNotifications() {
  notification = document.querySelector('[data-notification]');
}

export function notify(message) {
  if (!notification) return;
  window.clearTimeout(hideTimer);
  notification.textContent = message;
  notification.hidden = false;
  hideTimer = window.setTimeout(() => {
    notification.hidden = true;
  }, 7000);
}

