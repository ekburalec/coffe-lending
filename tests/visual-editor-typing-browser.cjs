async page => {
    const base = new URL(page.url()).origin;
    await page.setViewportSize({width: 1600, height: 1050});
    await page.goto(base + '/admin/visual-editor');
    const preview = page;
    const heading = preview.locator('[data-visual-text="copy-1"]');
    await page.waitForFunction(() => document.querySelector('[data-visual-text="copy-1"]').isContentEditable);
    if (await page.locator('[data-visual-login]').isVisible() || await page.locator('[data-visual-message].is-error,.notify--error').count()) throw new Error('Initial preview showed a false error');
    await heading.click();
    await heading.press('Control+a');
    await page.keyboard.type('Проверка посимвольного ввода');
    if (await heading.innerText() !== 'Проверка посимвольного ввода') throw new Error('Typing lost caret or characters');
    await heading.evaluate(element => {
        const transfer = new DataTransfer();
        transfer.setData('text/plain', 'Обычный текст');
        transfer.setData('text/html', '<a href="https://example.invalid"><strong>Чужой HTML</strong></a>');
        element.dispatchEvent(new ClipboardEvent('paste', {clipboardData: transfer, bubbles: true, cancelable: true}));
    });
    if (await heading.locator('a').count() || (await heading.innerText()).includes('Чужой HTML') || !(await heading.innerText()).includes('Обычный текст')) throw new Error('Paste retained foreign markup');
    await page.locator('[data-visual-cancel]').click();
    await page.evaluate(() => document.fonts.ready);
    await heading.evaluate(element => element.ownerDocument.fonts.ready);
    await page.screenshot({path: 'var/visual-editor-desktop.png', fullPage: false});
    await page.setViewportSize({width: 390, height: 900});
    await page.screenshot({path: 'var/visual-editor-mobile.png', fullPage: false});
    return 'PASS: character typing/caret, plain paste and final desktop/mobile screenshots';
}
