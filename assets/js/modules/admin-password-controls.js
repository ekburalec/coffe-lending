import { notify } from './notify.js';
import { PASSWORD_WORDS } from './password-words.js';

/** Показ паролей и локальное предложение нового пароля; сохранение только формой. */
export function initAdminPasswordControls() {
    const controls = [...document.querySelectorAll('[data-password-control]')];
    if (!controls.length) return;
    const visibility = new Map();
    controls.forEach((control) => {
        const input = control.querySelector('input');
        const toggle = control.querySelector('[data-password-toggle]');
        if (!input || !toggle) return;
        const eye = toggle.querySelector('[data-password-eye]');
        const eyeOff = toggle.querySelector('[data-password-eye-off]');
        if (!eye || !eyeOff) return;
        function show(visible) {
            input.type = visible ? 'text' : 'password';
            toggle.setAttribute('aria-label', visible ? 'Скрыть пароль' : 'Показать пароль');
            toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
            eye.hidden = visible;
            eyeOff.hidden = !visible;
        }
        visibility.set(input, show);
        toggle.addEventListener('click', () => show(input.type === 'password'));
        input.form?.addEventListener('submit', () => show(false));
        toggle.hidden = false;
    });

    const generator = document.querySelector('[data-password-generator]');
    if (!generator) return;
    const password = document.getElementById(generator.dataset.passwordTarget);
    const confirmation = document.getElementById(generator.dataset.passwordConfirmation);
    const generate = generator.querySelector('[data-password-generate]');
    const copy = generator.querySelector('[data-password-copy]');
    const feedback = document.querySelector('[data-password-feedback]');
    const length = generator.querySelector('[data-password-length]');
    const groupInputs = [...generator.querySelectorAll('[data-password-group]')];
    const symbols = generator.querySelector('[data-password-custom-symbols]');
    const excluded = generator.querySelector('[data-password-excluded-chars]');
    const requireGroups = generator.querySelector('[data-password-require-groups]');
    const mode = generator.querySelector('[data-password-mode]');
    const wordCount = generator.querySelector('[data-password-word-count]');
    const characterOptions = generator.querySelector('[data-password-character-options]');
    const phraseOptions = generator.querySelector('[data-password-phrase-options]');
    if (!password || !confirmation || !generate || !copy || !feedback || !length || !symbols || !excluded || !requireGroups || !groupInputs.length || !mode || !wordCount || !characterOptions || !phraseOptions) return;
    const GROUPS = {uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', lowercase: 'abcdefghijklmnopqrstuvwxyz', digits: '0123456789'};
    const SIMILAR = '0Oo1Il';
    // Самое длинное слово EFF: 9 ASCII-знаков; плюс разделитель. Ничего не обрезаем.
    const MAX_PHRASE_WORDS = Math.min(8, Math.floor((Number(generator.dataset.passwordMaxBytes) + 1) / 10));
    wordCount.max = String(MAX_PHRASE_WORDS);
    wordCount.value = String(Math.min(7, MAX_PHRASE_WORDS));
    mode.addEventListener('change', () => {
        const phrase = mode.value === 'phrase';
        characterOptions.hidden = phrase;
        phraseOptions.hidden = !phrase;
    });

    // Каждый индекс имеет одинаковую вероятность, включая алфавиты не степени двойки.
    function randomPassword(size, alphabet) {
        if (alphabet.length < 2) throw new Error('Недостаточно разрешённых символов.');
        const limit = Math.floor(256 / alphabet.length) * alphabet.length;
        let result = '';
        for (let attempt = 0; attempt < 128 && result.length < size; attempt++) {
            const bytes = crypto.getRandomValues(new Uint8Array(64));
            for (const byte of bytes) {
                if (byte >= limit) continue;
                result += alphabet[byte % alphabet.length];
                if (result.length === size) return result;
            }
        }
        throw new Error('Не удалось получить случайные значения.');
    }
    function reportError(message) {
        feedback.textContent = message;
        notify(message, 'error');
    }
    function syncCopy() {
        copy.disabled = password.value.length === 0;
        feedback.textContent = '';
    }
    function publish(value, message, entropyBits) {
        visibility.get(password)?.(false);
        visibility.get(confirmation)?.(false);
        password.value = value;
        confirmation.value = value;
        password.dispatchEvent(new Event('input', {bubbles: true}));
        confirmation.dispatchEvent(new Event('input', {bubbles: true}));
        password.dispatchEvent(new CustomEvent('password-generated', {detail: {entropyBits}}));
        feedback.textContent = message;
    }
    function generatePhrase() {
        const size = Number(wordCount.value);
        if (!wordCount.value.trim() || !Number.isInteger(size) || size < 6 || size > MAX_PHRASE_WORDS) {
            wordCount.setAttribute('aria-invalid', 'true');
            reportError('Укажите целое число слов от 6 до ' + MAX_PHRASE_WORDS + '.');
            wordCount.focus();
            return;
        }
        wordCount.removeAttribute('aria-invalid');
        try {
            const words = [];
            const limit = Math.floor(65536 / PASSWORD_WORDS.length) * PASSWORD_WORDS.length;
            for (let attempt = 0; attempt < 128 && words.length < size; attempt++) {
                const values = crypto.getRandomValues(new Uint16Array(16));
                for (const value of values) {
                    if (value >= limit) continue;
                    words.push(PASSWORD_WORDS[value % PASSWORD_WORDS.length]);
                    if (words.length === size) break;
                }
            }
            if (words.length !== size) throw new Error('Не удалось получить случайные слова.');
            publish(words.join(' '), 'Парольная фраза сгенерирована. Скопируйте её перед сохранением.', size * Math.log2(PASSWORD_WORDS.length));
        } catch {
            reportError('Не удалось сгенерировать фразу. Попробуйте ещё раз или введите пароль вручную.');
        }
    }
    generate.addEventListener('click', () => {
        if (mode.value === 'phrase') {
            generatePhrase();
            return;
        }
        const size = Number(length.value);
        if (!length.value.trim() || !Number.isInteger(size) || size < 16 || size > 64) {
            length.setAttribute('aria-invalid', 'true');
            reportError('Укажите целую длину от 16 до 64 символов.');
            length.focus();
            return;
        }
        length.removeAttribute('aria-invalid');
        symbols.removeAttribute('aria-invalid');
        const selected = groupInputs.filter(input => input.checked);
        if (!selected.length) {
            reportError('Выберите хотя бы одну группу символов.');
            return;
        }
        if (selected.some(input => input.dataset.passwordGroup === 'symbols') && /[^\x21-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e]/.test(symbols.value)) {
            symbols.setAttribute('aria-invalid', 'true');
            reportError('В наборе спецсимволов используйте знаки с английской раскладки без букв, цифр и пробелов.');
            return;
        }
        const blacklist = excluded.value + SIMILAR;
        const groups = selected.map(input => [...new Set(input.dataset.passwordGroup === 'symbols' ? symbols.value : GROUPS[input.dataset.passwordGroup])].filter(char => !blacklist.includes(char)));
        if (groups.some(group => !group.length)) {
            reportError('В одной из выбранных групп не осталось символов. Измените набор или исключения.');
            return;
        }
        const alphabet = [...new Set(groups.flat())].join('');
        if (alphabet.length < 2) {
            reportError('Оставьте хотя бы два разных символа для генерации.');
            return;
        }
        let value;
        try {
            for (let attempt = 0; attempt < 256; attempt++) {
                const candidate = randomPassword(size, alphabet);
                if (!requireGroups.checked || groups.every(group => [...candidate].some(char => group.includes(char)))) {
                    value = candidate;
                    break;
                }
            }
            if (!value) throw new Error('Не удалось выполнить условия состава.');
        } catch {
            reportError('Не удалось сгенерировать пароль. Попробуйте ещё раз или введите его вручную.');
            return;
        }
        const entropyBits = requireGroups.checked
            ? (size - groups.length) * Math.log2(alphabet.length) + groups.reduce((bits, group) => bits + Math.log2(group.length), 0)
            : size * Math.log2(alphabet.length);
        publish(value, 'Пароль сгенерирован. Скопируйте его перед сохранением.', entropyBits);
        if (size * Math.log2(alphabet.length) < 80) feedback.textContent += ' Для такого набора лучше увеличить длину или разрешить больше символов.';
    });
    // Настройки не должны отправлять ни свою форму, ни форму смены пароля по Enter.
    length.form?.addEventListener('submit', event => event.preventDefault());
    generator.addEventListener('keydown', event => {
        if (event.key !== 'Enter' || event.target.tagName !== 'INPUT') return;
        event.preventDefault();
        generate.click();
    });
    copy.addEventListener('click', async () => {
        try {
            if (!navigator.clipboard?.writeText) throw new Error('Буфер обмена недоступен.');
            await navigator.clipboard.writeText(password.value);
            feedback.textContent = 'Пароль скопирован.';
            notify(feedback.textContent, 'success');
        } catch {
            feedback.textContent = 'Не удалось скопировать. Покажите пароль и скопируйте его вручную.';
            notify(feedback.textContent, 'error');
        }
    });
    password.addEventListener('input', syncCopy);
    syncCopy();
    generator.hidden = false;
    if (!globalThis.crypto?.getRandomValues) {
        generate.disabled = true;
        feedback.textContent = 'Генерация недоступна в этом браузере. Введите новый пароль вручную.';
    }
}
