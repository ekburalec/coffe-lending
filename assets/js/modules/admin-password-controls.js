import { notify } from './notify.js';

/** Показ паролей и локальное предложение нового пароля; сохранение только формой. */
export function initAdminPasswordControls() {
    const controls = [...document.querySelectorAll('[data-password-control]')];
    if (!controls.length) return;
    controls.forEach((control) => {
        const input = control.querySelector('input');
        const toggle = control.querySelector('[data-password-toggle]');
        if (!input || !toggle) return;
        const eye = toggle.querySelector('[data-password-eye]');
        const eyeOff = toggle.querySelector('[data-password-eye-off]');
        function show(visible) {
            input.type = visible ? 'text' : 'password';
            toggle.setAttribute('aria-label', visible ? 'Скрыть пароль' : 'Показать пароль');
            toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
            eye.hidden = visible;
            eyeOff.hidden = !visible;
        }
        toggle.addEventListener('click', () => show(input.type === 'password'));
        input.form?.addEventListener('submit', () => show(false));
        toggle.hidden = false;
    });

    const generator = document.querySelector('[data-password-generator]');
    if (!generator || !globalThis.crypto?.getRandomValues) return;
    const password = document.getElementById(generator.dataset.passwordTarget);
    const confirmation = document.getElementById(generator.dataset.passwordConfirmation);
    const generate = generator.querySelector('[data-password-generate]');
    const copy = generator.querySelector('[data-password-copy]');
    const feedback = document.querySelector('[data-password-feedback]');
    if (!password || !confirmation || !generate || !copy || !feedback) return;
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
    function syncCopy() {
        copy.disabled = password.value.length === 0;
        feedback.textContent = '';
    }
    generate.addEventListener('click', () => {
        // В алфавите 64 символа: каждый индекс получает одинаковую вероятность.
        const bytes = crypto.getRandomValues(new Uint8Array(20));
        const value = [...bytes].map((byte) => alphabet[byte & 63]).join('');
        password.value = value;
        confirmation.value = value;
        password.dispatchEvent(new Event('input', {bubbles: true}));
        confirmation.dispatchEvent(new Event('input', {bubbles: true}));
        feedback.textContent = 'Пароль сгенерирован. Скопируйте его перед сохранением.';
    });
    copy.addEventListener('click', async () => {
        try {
            if (!navigator.clipboard?.writeText) throw new Error('Буфер обмена недоступен.');
            await navigator.clipboard.writeText(password.value);
            feedback.textContent = 'Пароль скопирован.';
            notify(feedback.textContent, 'success');
        } catch (error) {
            feedback.textContent = 'Не удалось скопировать. Покажите пароль и скопируйте его вручную.';
            notify(feedback.textContent, 'error');
            console.error(error);
        }
    });
    password.addEventListener('input', syncCopy);
    syncCopy();
    generator.hidden = false;
}
