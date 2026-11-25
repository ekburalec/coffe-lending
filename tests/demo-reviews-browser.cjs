async (page) => {
    const base = 'http://localhost:8090';
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => { if (response.status() >= 400) errors.push(response.status() + ' ' + response.url()); });
    await page.goto(base + '/#reviews');
    for (const width of [1440, 768, 320]) {
        await page.setViewportSize({width, height: 1000});
        await page.locator('#reviews').scrollIntoViewIfNeeded();
        await page.waitForFunction(() => [...document.querySelectorAll('.reviews__avatar')].every(el => el.complete && el.naturalWidth > 0));
        if (await page.locator('.reviews__avatar').count() !== 3 || await page.locator('.reviews__demo').count() !== 3) throw new Error('Demo portraits/disclosures missing');
        if (await page.locator('.reviews__source').count()) throw new Error('Invented review sources');
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Page overflow at ' + width);
        const columns = await page.locator('.reviews__track').evaluate(el => Math.round(el.clientWidth / (el.firstElementChild.getBoundingClientRect().width + 24)));
        if (columns !== (width >= 640 ? 2 : 1)) throw new Error('Review columns at ' + width);
        await page.locator('#reviews').screenshot({path: 'var/demo-reviews-' + width + '.png'});
    }
    await page.goto(base + '/admin/edit?section=reviews&id=demo-review-anna');
    if (!await page.locator('#field-is_demo').isChecked()) throw new Error('Demo flag missing');
    if (!(await page.locator('#field-image').inputValue()).includes('placeholders/avatar.webp')) throw new Error('Photo editor missing');
    const upload = page.locator('input[type=file]');
    if (!await upload.evaluate(el => el.classList.contains('admin__file-input') && getComputedStyle(el, '::file-selector-button').borderRadius === '999px')) throw new Error('Review upload styling');
    for (const width of [1440, 768, 320]) {
        await page.setViewportSize({width, height: 1000});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Review editor overflow');
    }
    await page.goto(base + '/admin/edit?section=reviews');
    if (await page.locator('#add-record form').getAttribute('enctype') !== 'multipart/form-data') throw new Error('Inline photo form');
    if (await page.locator('.admin__badge', {hasText: 'Демо'}).count() !== 3) throw new Error('Admin demo badges');
    await page.setViewportSize({width: 1440, height: 1000});
    await page.locator('#records').screenshot({path: 'var/demo-reviews-admin.png'});
    const noJs = await page.context().browser().newContext({javaScriptEnabled: false});
    const plain = await noJs.newPage();
    await plain.goto(base + '/#reviews');
    if (await plain.locator('.reviews__demo').count() !== 3 || !await plain.locator('.reviews__quote').first().isVisible()) throw new Error('No-JS reviews');
    await noJs.close();
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: demo labels, three portraits, retina, 2/2/1 cards, no JS, photo editor and responsive layouts';
}
