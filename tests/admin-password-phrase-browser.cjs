async (page) => {
    const base = new URL(page.url()).origin;
    await page.addInitScript(() => {
        Object.defineProperty(navigator, 'clipboard', {configurable: true, value: {writeText: async value => { window.qaPhraseCopy = value; }}});
    });
    await page.goto(base + '/admin/password');
    const mode = page.locator('[data-password-mode]');
    const count = page.locator('[data-password-word-count]');
    const password = page.locator('#new-password');
    const confirmation = page.locator('#confirm-password');
    const generate = page.locator('[data-password-generate]');
    let posts = 0;
    page.on('request', request => { if (request.method() === 'POST') posts++; });
    await mode.selectOption('phrase');
    if (await count.inputValue() !== '7' || !await page.locator('[data-password-character-options]').isHidden()) throw new Error('Phrase defaults or hidden character settings');
    await generate.click();
    const initial = await password.inputValue();
    if (initial.split(' ').length !== 7 || !/^[a-z-]+(?: [a-z-]+){6}$/.test(initial) || await confirmation.inputValue() !== initial) throw new Error('Phrase generation');
    await page.locator('[data-password-copy]').click();
    if (!await page.evaluate(value => window.qaPhraseCopy === value, initial)) throw new Error('Phrase copy');
    for (const invalid of ['5', '9', '6.5', '']) {
        await count.fill(invalid);
        await generate.click();
        if (await password.inputValue() !== initial || await count.getAttribute('aria-invalid') !== 'true') throw new Error('Invalid phrase count changed input');
    }
    for (const size of ['6', '8']) {
        await count.fill(size);
        await generate.click();
        if ((await password.inputValue()).split(' ').length !== Number(size)) throw new Error('Phrase count boundary');
    }
    await count.fill('7');
    await page.evaluate(() => {
        window.qaOriginalPhraseRandom = crypto.getRandomValues.bind(crypto);
        crypto.getRandomValues = array => {
            const values = [62208, 65535, 0, 62207, 7776, 1];
            array.forEach((_, i) => { array[i] = values[i % values.length]; });
            return array;
        };
    });
    await generate.click();
    const boundary = 'abacus zoom abacus abdomen abacus zoom abacus';
    if (await password.inputValue() !== boundary) throw new Error('Word-list rejection boundaries');
    await page.evaluate(() => { crypto.getRandomValues = array => { array.fill(65535); return array; }; });
    await generate.click();
    if (await password.inputValue() !== boundary || !(await page.locator('[data-password-feedback]').innerText()).includes('Не удалось')) throw new Error('Phrase RNG failure or infinite loop');
    await page.evaluate(() => { crypto.getRandomValues = window.qaOriginalPhraseRandom; });
    await password.locator('..').getByRole('button').click();
    await confirmation.locator('..').getByRole('button').click();
    await count.press('Enter');
    if (await password.getAttribute('type') !== 'password' || await confirmation.getAttribute('type') !== 'password') throw new Error('Phrase keyboard generation should hide fields');
    const stable = await password.inputValue();
    await mode.selectOption('characters');
    if (await password.inputValue() !== stable || !await page.locator('[data-password-phrase-options]').isHidden()) throw new Error('Mode switch replaced password');
    await generate.click();
    if ((await password.inputValue()).length !== 20) throw new Error('Character mode after phrase');
    if (posts) throw new Error('Phrase generation submitted a form');
    const fallback = await page.context().browser().newContext({storageState: await page.context().storageState()});
    const limited = await fallback.newPage();
    await limited.route(base + '/admin/password', async route => {
        const response = await route.fetch();
        await route.fulfill({response, body: (await response.text()).replace(/data-password-max-bytes="\d+"/, 'data-password-max-bytes="72"')});
    });
    await limited.goto(base + '/admin/password');
    await limited.locator('[data-password-mode]').selectOption('phrase');
    if (await limited.locator('[data-password-word-count]').getAttribute('max') !== '7') throw new Error('Bcrypt phrase byte limit');
    await limited.locator('[data-password-generate]').click();
    if ((await limited.locator('#new-password').inputValue()).length > 72) throw new Error('Phrase exceeds bcrypt limit');
    await fallback.close();
    await page.goto(base + '/admin/password');
    return 'PASS: local phrases, word counts, Uint16 rejection boundaries, RNG failure, copy, hiding/keyboard, mode independence and bcrypt length limit';
}
