/** Компенсация высоты шапки через CSSOM, без inline-стилей в DOM. */
export function initFixedHeader() {
    const header = document.querySelector('[data-site-header]');
    const spacer = document.querySelector('[data-header-spacer]');
    if (!header || !spacer || !window.ResizeObserver) return;

    let heightRule;
    try {
        for (const sheet of document.styleSheets) {
            heightRule = findHeightRule(sheet.cssRules);
            if (heightRule) break;
        }
    } catch (error) {
        console.error('Не удалось настроить отступ фиксированной шапки:', error);
        return;
    }
    if (!heightRule) return;

    let previousHeight = 0;
    function syncHeight() {
        const height = Math.ceil(header.getBoundingClientRect().height);
        if (height === previousHeight) return;
        heightRule.style.setProperty('--site-header-height', `${height}px`);
        previousHeight = height;
    }

    header.classList.add('is-fixed');
    spacer.hidden = false;
    syncHeight();
    const observer = new ResizeObserver(syncHeight);
    observer.observe(header);
    header.addEventListener('click', syncHeight);
    header.addEventListener('keydown', syncHeight);
}

function findHeightRule(rules) {
    for (const rule of rules) {
        if (rule.style?.getPropertyValue('--site-header-height')) return rule;
        if (rule.styleSheet) {
            const found = findHeightRule(rule.styleSheet.cssRules);
            if (found) return found;
        }
    }
    return null;
}
