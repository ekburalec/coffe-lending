/** Локальная ориентировочная оценка нового пароля, без сети и хранения строки. */
export function initAdminPasswordStrength() {
    const input = document.querySelector('[data-password-strength-input]');
    const indicator = document.querySelector('[data-password-strength]');
    if (!input || !indicator) return;
    const meter = indicator.querySelector('[data-password-strength-meter]');
    const status = indicator.querySelector('[data-password-strength-status]');
    const segments = [...indicator.querySelectorAll('[data-password-strength-segment]')];
    if (!meter || !status || segments.length !== 4) return;
    const LABELS = ['Введите пароль', 'Слабый', 'Средний', 'Надёжный', 'Отличный'];
    const COMMON = new Set(['passwordpassword', 'password123456789', 'adminadminadmin',
        'administrator123', 'letmeinletmeinletmein', 'changemechangeme',
        'correcthorsebatterystaple', 'парольпарольпароль']);

    function hasWeakPattern(value) {
        const original = value.normalize('NFKC').replace(/\s+/gu, '');
        const compact = original.toLowerCase();
        if (!compact || COMMON.has(compact)) return true;
        if (/^(?:password|пароль|qwerty|admin|administrator)\d*$/u.test(compact)) return true;
        for (const sequence of ['0123456789', 'abcdefghijklmnopqrstuvwxyz', 'qwertyuiopasdfghjklzxcvbnm']) {
            if (sequence.includes(compact) || [...sequence].reverse().join('').includes(compact)) return true;
        }
        for (let size = 1; size <= Math.min(32, Math.floor(original.length / 2)); size++) {
            if (original.slice(0, size).repeat(Math.ceil(original.length / size)).slice(0, original.length) === original) return true;
        }
        return false;
    }
    function levelFor(value, entropyBits) {
        if (!value) return 0;
        if (value.length > 1024) return 1;
        const characters = [...value];
        if (characters.length < 15 || hasWeakPattern(value)) return 1;
        if (Number.isFinite(entropyBits) && entropyBits >= 0) {
            if (entropyBits < 50) return 1;
            if (entropyBits < 70) return 2;
            return entropyBits < 100 ? 3 : 4;
        }
        // Ручной ввод не получает биты энтропии или обещание максимальной стойкости.
        const unique = new Set(characters).size;
        if (characters.length >= 20 && unique >= 10) return 3;
        return unique >= 8 ? 2 : 1;
    }
    function update(entropyBits) {
        const level = levelFor(input.value, entropyBits);
        segments.forEach((segment, index) => segment.classList.toggle('is-active', index < level));
        meter.setAttribute('aria-valuenow', String(level));
        meter.setAttribute('aria-valuetext', LABELS[level]);
        const text = level ? 'Оценка: ' + LABELS[level] : LABELS[0];
        if (status.textContent !== text) status.textContent = text;
    }
    input.addEventListener('input', () => update());
    input.addEventListener('change', () => update());
    input.addEventListener('password-generated', event => update(event.detail?.entropyBits));
    input.form?.addEventListener('reset', () => queueMicrotask(() => update()));
    indicator.hidden = false;
    update();
}
