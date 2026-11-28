async (page) => {
    const base = new URL(page.url()).origin;
    await page.goto(base + '/admin/password');
    const summary = page.getByText('Дополнительные настройки', {exact: true});
    await summary.focus();
    await page.keyboard.press('Enter');
    if (!await page.locator('[data-password-custom-symbols]').isVisible()) throw new Error('Keyboard disclosure');
    const semantics = await page.evaluate(() => {
        const controls = [...document.querySelectorAll('input:not([type=hidden]), select')];
        return controls.every(input => input.labels.length > 0)
            && [...document.querySelectorAll('[aria-describedby]')].every(input => input.getAttribute('aria-describedby').split(/\s+/).every(id => document.getElementById(id)))
            && [...document.querySelectorAll('[id]')].length === new Set([...document.querySelectorAll('[id]')].map(node => node.id)).size
            && document.querySelectorAll('form form').length === 0
            && [...document.querySelectorAll('[data-password-generator] input')].every(input => input.form?.id === 'password-generator-settings');
    });
    if (!semantics) throw new Error('Form labels, descriptions, IDs or ownership');
    const pasteAllowed = await page.locator('#new-password').evaluate(input => {
        const clipboardData = new DataTransfer();
        clipboardData.setData('text/plain', 'manual pasted value');
        return input.dispatchEvent(new ClipboardEvent('paste', {bubbles: true, cancelable: true, clipboardData}));
    });
    if (!pasteAllowed) throw new Error('Password paste is blocked');
    await page.locator('#new-password').focus();
    await page.keyboard.insertText('manual pasted value');
    if (await page.locator('#new-password').inputValue() !== 'manual pasted value') throw new Error('Inserted password text was modified');
    await page.locator('#new-password').fill('');
    await page.locator('[data-password-group=uppercase]').focus();
    if (await page.locator('[data-password-group=uppercase]').evaluate(input => getComputedStyle(input).outlineStyle === 'none')) throw new Error('Missing keyboard focus');
    for (const width of [320, 768, 1440]) {
        await page.setViewportSize({width, height: 1000});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Expanded settings overflow at ' + width);
        await page.screenshot({path: 'var/password-settings-' + width + '.png', fullPage: true});
    }
    await page.evaluate(() => document.body.classList.add('dark-mode'));
    if (!await page.locator('[data-password-custom-symbols]').evaluate(input => getComputedStyle(input).color !== getComputedStyle(input).backgroundColor)) throw new Error('Dark theme field contrast');
    await page.evaluate(() => document.body.classList.remove('dark-mode'));
    await page.locator('[data-password-length]').fill('');
    await page.locator('#current-password').fill('manual-current-only');
    await page.locator('#new-password').fill('manual-new-only-value');
    await page.locator('#confirm-password').fill('manual-new-only-value');
    const manual = await page.locator('#new-password').evaluate(input => input.form.checkValidity() && !new FormData(input.form).has('password-length'));
    if (!manual) throw new Error('Settings constrain manual change');
    await page.goto(base + '/admin/password');
    return 'PASS: keyboard disclosure/focus, labels/descriptions/unique IDs, separate form ownership, expanded 320/768/1440, dark tokens and manual validation';
}
