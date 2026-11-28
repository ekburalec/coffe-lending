async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(base + '/#contacts');
    await page.reload();
    const footer = page.locator('.site-footer');
    const links = footer.locator('[data-social-placeholder]');
    if (await links.count() !== 5) throw new Error('Five social placeholders expected');
    if (await links.locator('svg[aria-hidden="true"][focusable="false"] use').count() !== 5) throw new Error('Five decorative SVG icons expected');
    const destinations = await links.evaluateAll(nodes => nodes.map(node => node.getAttribute('href')));
    if (destinations.some(url => url !== '#contacts')) throw new Error('Invented social account');
    await links.first().click();
    if (!await page.getByRole('status').filter({hasText: 'пока не добавлена'}).isVisible()) throw new Error('Placeholder feedback missing');
    for (const width of [1440, 768, 320]) {
        await page.setViewportSize({width, height: 1000});
        const columns = await footer.locator('.site-footer__inner').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length);
        if (columns !== (width >= 768 ? 2 : 1)) throw new Error('Footer columns at ' + width);
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Footer overflow at ' + width);
    }
    await page.setViewportSize({width: 1440, height: 1000});
    await footer.screenshot({path: 'var/footer-two-columns.png'});
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: two-column footer, five honest social placeholders and responsive layouts';
}
