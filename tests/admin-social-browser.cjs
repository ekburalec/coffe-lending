async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(base + '/admin');
    await page.getByRole('navigation', {name: 'Разделы управления'}).getByRole('link', {name: 'Соцсети', exact: true}).click();
    if (new URL(page.url()).searchParams.get('section') !== 'social') throw new Error('Social navigation');
    if (await page.locator('fieldset').count() !== 5) throw new Error('Five social groups expected');
    if (await page.locator('[name$="_name"]').count() !== 5 || await page.locator('[name$="_label"]').count() !== 5 || await page.locator('[name$="_url"]').count() !== 5) throw new Error('Editable name/label/URL fields');
    const color = await page.locator('.admin__nav-link[aria-current]').evaluate(el => getComputedStyle(el).backgroundColor);
    if (await page.getByRole('checkbox', {name: 'Показывать на сайте', exact: true}).count() !== 5) throw new Error('Five social visibility controls expected');
    if (color !== 'rgb(139, 68, 45)') throw new Error('Palette changed');
    for (const width of [1440, 768, 320]) {
        await page.setViewportSize({width, height: 1000});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Social editor overflow at ' + width);
    }
    await page.setViewportSize({width: 1440, height: 1000});
    await page.screenshot({path: 'var/admin-social-editor.png', fullPage: true});
    await page.goto(base + '/admin/edit?section=contacts');
    if (await page.locator('[name="vk_url"]').count()) throw new Error('Social URL still duplicated in contacts');
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: separate social menu, five groups, editable names/labels/URLs, coffee palette and responsive layout';
}
