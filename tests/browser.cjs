async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => { if (response.status() >= 400) errors.push(response.url()); });
    await page.goto(base + '/');
    for (const [americano, cappuccino, expected] of [[1, 0, 340], [1, 3, 1000], [2, 3, 1190]]) {
        await page.reload();
        for (let n = 0; n < americano; n++) await page.locator('[data-order-add="americano"]').click();
        for (let n = 0; n < cappuccino; n++) await page.locator('[data-order-add="cappuccino"]').click();
        await page.locator('[data-order-open]').first().click();
        await page.locator('#courier').check();
        const total = Number((await page.locator('[data-order-total]').innerText()).replace(/[^0-9]/g, ''));
        if (total !== expected) throw new Error(`Cart: ${total} != ${expected}`);
        await page.keyboard.press('Escape');
        if (await page.locator('[data-order-drawer]').evaluate(el => el.open)) throw new Error('Escape did not close cart');
    }
    await page.reload();
    await page.locator('[data-order-open]').first().click();
    if (!await page.locator('[data-order-empty]').isVisible()) throw new Error('Empty cart missing');
    await page.keyboard.press('Escape');
    for (const width of [320, 768, 1440]) {
        await page.setViewportSize({width, height: 900});
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth);
        if (overflow) throw new Error('Horizontal overflow at ' + width);
    }
    await page.setViewportSize({width: 320, height: 900});
    await page.locator('[data-navigation-toggle]').click();
    if (!await page.locator('[data-navigation]').isVisible()) throw new Error('Mobile navigation did not open');
    await page.keyboard.press('Escape');
    if (await page.locator('[data-navigation]').isVisible()) throw new Error('Mobile navigation did not close');
    const noJs = await page.context().browser().newContext({javaScriptEnabled: false});
    const plain = await noJs.newPage();
    await plain.goto(base + '/');
    if (await plain.locator('[data-product]').count() !== 9) throw new Error('No-JS menu missing');
    if (!await plain.locator('[data-unit-price]').first().isVisible()) throw new Error('No-JS prices missing');
    await noJs.close();
    const images = await page.locator('img').evaluateAll(nodes => nodes.every(el => el.alt !== undefined && el.width && el.height && el.srcset));
    if (!images) throw new Error('Image metadata missing');
    await page.route(base + '/', async route => {
        const response = await route.fetch();
        const html = (await response.text()).replace(/<ul class="site-header__list">[\s\S]*?<\/ul>/, '<ul class="site-header__list"></ul>');
        await route.fulfill({response, body: html});
    });
    await page.goto(base + '/');
    await page.setViewportSize({width: 320, height: 900});
    await page.setViewportSize({width: 1440, height: 900});
    if (!await page.locator('[data-navigation-toggle]').isHidden()) throw new Error('Empty navigation toggle visible');
    await page.unroute(base + '/');
    await page.goto(base + '/');
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: cart below/on/above threshold, empty cart, Escape, responsive widths, resources';
}
