async (page) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => { if (response.status() >= 400) errors.push(response.url()); });
    await page.goto('http://localhost:8090/');
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
    const images = await page.locator('img').evaluateAll(nodes => nodes.every(el => el.alt !== undefined && el.width && el.height && el.srcset));
    if (!images) throw new Error('Image metadata missing');
    if (errors.length) throw new Error(errors.join('\n'));
    console.log('PASS: cart below/on/above threshold, empty cart, Escape, responsive widths, resources');
}
