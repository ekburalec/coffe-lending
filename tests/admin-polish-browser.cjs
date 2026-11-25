async (page) => {
    const base = 'http://localhost:8090';
    const failures = [];
    page.on('pageerror', error => failures.push(error.message));
    await page.goto(base + '/admin/edit?section=faq');
    const link = page.locator('.admin__site-link');
    if (await link.getAttribute('target') !== '_blank' || !(await link.getAttribute('rel')).includes('noopener')) throw new Error('Site link safety');
    const popupPromise = page.waitForEvent('popup');
    await link.click();
    const popup = await popupPromise;
    await popup.waitForLoadState();
    if (new URL(popup.url()).pathname !== '/') throw new Error('Site popup destination');
    await popup.close();
    for (const section of ['faq', 'content', 'images', 'seo', 'backups']) {
        await page.goto(base + (section === 'backups' ? '/admin/backups' : '/admin/edit?section=' + section));
        if (!await page.locator('.admin__nav-link[aria-current]').count()) throw new Error('Authentication lost');
        for (const width of [1440, 768, 320]) {
            await page.setViewportSize({width, height: 900});
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error(section + ' overflow at ' + width);
            if (section === 'faq' || section === 'content') {
                const button = page.locator('.admin__button--compact').first();
                const box = await button.boundingBox();
                if (box.width > 100 || (width === 1440 && box.height > 36)) throw new Error('Oversized row action');
                if (section === 'faq' && width === 1440) {
                    const right = await page.locator('.admin__actions--row').first().evaluate(el => el.getBoundingClientRect().right);
                    const row = await page.locator('.admin__row').first().boundingBox();
                    if (row.x + row.width - right > 20) throw new Error('Actions not right aligned');
                }
            }
        }
        const input = page.locator('input[type=file]').first();
        if (await input.count()) {
            const style = await input.evaluate(el => ({radius: getComputedStyle(el, '::file-selector-button').borderRadius, border: getComputedStyle(el).borderStyle}));
            if (style.border !== 'dashed' || parseFloat(style.radius) < 100) throw new Error('Unstyled file selector');
        }
    }
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(base + '/admin/edit?section=faq');
    await page.screenshot({path: 'var/admin-compact-actions.png'});
    if (failures.length) throw new Error(failures.join('\n'));
    return 'PASS: compact right actions, styled upload inputs, new tab, palette and 320/768/1440 layouts';
}
