import { notify } from './notify.js';

export function initNewsletter() {
  const form = document.querySelector('[data-newsletter]');
  if (!form) return;
  const email = form.querySelector('[data-newsletter-email]');
  const consent = form.querySelector('[data-newsletter-consent]');
  const submit = form.querySelector('[data-newsletter-submit]');
  const error = form.querySelector('[data-newsletter-error]');
  if (!email || !consent || !submit || !error) return;

  email.disabled = false;
  consent.disabled = false;
  submit.disabled = false;

  function clearError() {
    error.hidden = true;
    error.textContent = '';
    email.removeAttribute('aria-invalid');
    consent.removeAttribute('aria-invalid');
  }

  email.addEventListener('input', clearError);
  consent.addEventListener('change', clearError);
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    clearError();
    email.value = email.value.trim();
    if (!email.validity.valid) {
      error.textContent = email.value ? 'Проверьте адрес электронной почты.' : 'Укажите ваш email.';
      error.hidden = false;
      email.setAttribute('aria-invalid', 'true');
      email.focus();
      return;
    }
    if (!consent.checked) {
      error.textContent = 'Для подписки необходимо ваше согласие.';
      error.hidden = false;
      consent.setAttribute('aria-invalid', 'true');
      consent.focus();
      return;
    }
    notify('Подписка пока недоступна. Загляните позже — будем рады вам!');
  });
}

