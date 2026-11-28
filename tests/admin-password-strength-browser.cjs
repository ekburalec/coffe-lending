async (page) => {
    const base = new URL(page.url()).origin;
    await page.goto(base + '/admin/password');
    const input = page.locator('[data-password-strength-input]');
    const meter = page.locator('[data-password-strength-meter]');
    const segments = page.locator('[data-password-strength-segment]');
    const generate = page.locator('[data-password-generate]');
    const length = page.locator('[data-password-length]');
    const status = page.locator('[data-password-strength-status]');
    const network = [];
    page.on('request', request => { if (['fetch', 'xhr'].includes(request.resourceType()) || request.method() === 'POST') network.push(request.url()); });
    async function expectLevel(level, text) {
        if (await meter.getAttribute('aria-valuenow') !== String(level)
            || await meter.getAttribute('aria-valuetext') !== text
            || await page.locator('[data-password-strength-segment].is-active').count() !== level
            || !(await status.innerText()).includes(text)) throw new Error('Strength status/segments/ARIA do not match');
    }
    if (await page.locator('[data-password-exclude-similar]').count()) throw new Error('Ambiguous-character checkbox must be removed');
    if (await segments.count() !== 4 || await meter.getAttribute('role') !== 'meter') throw new Error('Meter semantics');
    await expectLevel(0, 'Введите пароль');
    for (const weak of ['short', 'A'.repeat(40), 'abc123'.repeat(5), 'abcdefghijklmnopqrstuvwxyz', 'password20262026', 'correct horse battery staple']) {
        await input.fill(weak);
        await expectLevel(1, 'Слабый');
    }
    await input.fill('M7!b2G#v9K$p4Rs');
    await expectLevel(2, 'Средний');
    await input.fill('Coffee breaks with friends');
    await expectLevel(3, 'Надёжный');
    await page.evaluate(() => {
        window.qaStrengthRandom = crypto.getRandomValues.bind(crypto);
        crypto.getRandomValues = array => {
            const values = [0, 24, 48, 56, 3, 28, 51, 57, 6, 31, 54, 20, 9, 34, 49, 23, 12, 37, 52, 16];
            array.forEach((_, index) => { array[index] = values[index % values.length]; });
            return array;
        };
        document.querySelector('[data-password-strength-input]').addEventListener('password-generated', event => {
            window.qaStrengthDetailKeys = Object.keys(event.detail);
        });
    });
    await generate.click();
    await expectLevel(4, 'Отличный');
    if (!await page.evaluate(() => JSON.stringify(window.qaStrengthDetailKeys) === '["entropyBits"]')) throw new Error('Generation event leaks values');
    const generated = await input.inputValue();
    if (/[0Oo1Il]/.test(generated)) throw new Error('Ambiguous characters are not automatically filtered');
    await length.fill('16');
    await expectLevel(4, 'Отличный');
    await generate.click();
    await expectLevel(3, 'Надёжный');
    await length.fill('20');
    await generate.click();
    await input.locator('..').getByRole('button').click();
    await expectLevel(4, 'Отличный');
    await input.locator('..').getByRole('button').click();
    await input.dispatchEvent('input');
    await expectLevel(3, 'Надёжный');
    await page.getByText('Дополнительные настройки', {exact: true}).click();
    await page.locator('[data-password-require-groups]').check();
    await generate.click();
    await expectLevel(4, 'Отличный');
    await page.locator('[data-password-mode]').selectOption('phrase');
    await generate.click();
    await expectLevel(3, 'Надёжный');
    await page.locator('[data-password-word-count]').fill('8');
    await generate.click();
    await expectLevel(4, 'Отличный');
    for (const width of [320, 768, 1440]) {
        await page.setViewportSize({width, height: 1000});
        const sizes = await input.evaluate(node => {
            const meter = document.querySelector('[data-password-strength-meter]');
            const style = getComputedStyle(meter);
            return {inputWidth: node.getBoundingClientRect().width, barWidth: meter.getBoundingClientRect().width,
                height: meter.getBoundingClientRect().height, gap: parseFloat(style.columnGap),
                topGap: meter.getBoundingClientRect().top - node.getBoundingClientRect().bottom,
                fontSize: parseFloat(getComputedStyle(document.querySelector('[data-password-strength-status]')).fontSize),
                overflow: document.documentElement.scrollWidth > innerWidth};
        });
        if (Math.abs(sizes.inputWidth - sizes.barWidth) > .5 || sizes.height !== 6 || sizes.gap !== 4
            || sizes.topGap < 6 || sizes.topGap > 8.1 || sizes.fontSize !== 13 || sizes.overflow) throw new Error('Strength geometry at ' + width);
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({path: 'var/password-strength-' + width + '.png', fullPage: true});
    }
    for (const dark of [false, true]) {
        await page.evaluate(value => document.body.classList.toggle('dark-mode', value), dark);
        const colorsMatch = await page.evaluate(() => getComputedStyle(document.querySelector('.password-strength__segment.is-active')).backgroundColor
            === getComputedStyle(document.querySelector('[data-password-toggle]')).color);
        if (!colorsMatch) throw new Error('Strength indicator introduced a different palette');
    }
    await page.evaluate(() => document.body.classList.remove('dark-mode'));
    await page.evaluate(() => { crypto.getRandomValues = window.qaStrengthRandom; });
    await page.locator('[data-password-mode]').selectOption('characters');
    await page.getByText('Дополнительные настройки', {exact: true}).evaluate(summary => { summary.parentElement.open = true; });
    for (const group of await page.locator('[data-password-group]').all()) await group.uncheck();
    await page.locator('[data-password-group=symbols]').check();
    await page.locator('[data-password-custom-symbols]').fill('!_-');
    await length.fill('64');
    await generate.click();
    await expectLevel(4, 'Отличный');
    await input.fill('');
    await expectLevel(0, 'Введите пароль');
    await input.fill('Coffee breaks with friends');
    await input.evaluate(node => node.form.reset());
    await expectLevel(0, 'Введите пароль');
    if (network.length) throw new Error('Strength/generation sent requests');
    await page.evaluate(() => { crypto.getRandomValues = window.qaStrengthRandom; });
    await page.goto(base + '/admin/password');
    return 'PASS: automatic exclusions, local/manual/generated ratings, reset/change/show/phrase, segments/ARIA, 6px/4px/8px/13px sizing, existing light/dark palette and no network';
}
