async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', res => { if (res.status() >= 400) errors.push(res.status() + ' ' + res.url()); });
    await page.emulateMedia({reducedMotion: 'reduce'});
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(base + '/');
    if (await page.locator('h1').innerText() !== 'Повод зайти за кофе') throw new Error('H1 not updated');
    const root = page.locator('[data-occasions]');
    if (await root.getByRole('tab').count() !== 3 || await root.getByRole('tabpanel').count() !== 1) throw new Error('Occasion tabs');
    await root.getByRole('tab').nth(1).click();
    if (await root.getByRole('tabpanel').getByRole('heading').innerText() !== 'Пауза среди дня') throw new Error('Occasion selection');
    await page.keyboard.press('ArrowRight');
    if (await root.getByRole('tabpanel').getByRole('heading').innerText() !== 'Встреча без спешки') throw new Error('Occasion keyboard');
    await page.keyboard.press('Home');
    const link = root.getByRole('tabpanel').getByRole('link');
    const target = await link.getAttribute('href');
    await link.click();
    if (new URL(page.url()).hash !== target) throw new Error('Occasion destination');
    await root.screenshot({path: 'var/occasions-desktop.png'});
    const track = page.locator('[data-reviews-track]');
    if (await track.count()) {
        if (await track.locator('li').count() < 3) throw new Error('Fixture needs three reviews');
        await page.locator('[data-reviews-next]').click();
        await page.waitForFunction(() => document.querySelector('[data-reviews-track]').scrollLeft > 10);
        await page.locator('[data-reviews-previous]').click();
        await page.waitForFunction(() => document.querySelector('[data-reviews-track]').scrollLeft < 2);
        await track.focus();
        await page.keyboard.press('End');
        await page.waitForFunction(() => document.querySelector('[data-reviews-track]').scrollLeft > 10);
        await page.keyboard.press('Home');
    }
    for (const width of [320, 768, 1440]) {
        await page.setViewportSize({width, height: 1000});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Overflow at ' + width);
    }
    const noJs = await page.context().browser().newContext({javaScriptEnabled: false});
    const plain = await noJs.newPage();
    await plain.goto(base + '/');
    if (await plain.locator('[data-occasion-panel]:visible').count() !== 3 || !await plain.locator('[data-occasion-tabs]').isHidden()) throw new Error('No-JS occasions');
    await noJs.close();
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: shorter H1, occasion selection/keyboard/links/no-JS, review arrows/keyboard, responsive layout';
}
