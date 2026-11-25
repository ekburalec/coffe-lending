/** Подсказки SEO: длина текста без изменения введённого значения. */
export function initAdminCharacterCount() {
    document.querySelectorAll('[data-character-field]').forEach((field) => {
        const input = field.querySelector('input, textarea');
        const count = field.querySelector('[data-character-count]');
        if (!input || !count) return;
        const update = () => { count.textContent = Array.from(input.value).length; };
        input.addEventListener('input', update);
        update();
    });
}
