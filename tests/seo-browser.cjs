async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('response', response => { if (response.status() >= 400) errors.push(response.url()); });
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(base + '/admin/edit?section=seo');
    if (await page.locator('h1').count() !== 1 || await page.locator('#seo-texts form').count() !== 1 || await page.locator('#seo-preview form').count() !== 1) throw new Error('SEO sections structure');
    const color = await page.locator('.admin__nav-link[aria-current="page"]').evaluate(el => getComputedStyle(el).backgroundColor);
    if (color !== 'rgb(139, 68, 45)') throw new Error('Original palette changed');
    if (await page.locator('[data-character-field]').count() !== 5) throw new Error('SEO counters missing');
    const title = page.locator('#field-title');
    const original = await title.inputValue();
    await title.fill('Кофе ☕');
    if (await title.locator('..').locator('[data-character-count]').innerText() !== '6') throw new Error('Live Unicode character count');
    await title.fill(original);
    const image = page.locator('.admin__seo-preview');
    await image.scrollIntoViewIfNeeded();
    if (!await image.evaluate(el => el.complete && el.naturalWidth > 0 && el.srcset.includes('@2x'))) throw new Error('Preview/retina missing');
    await page.locator('#seo-texts').screenshot({path: 'var/seo-texts.png'});
    await page.locator('#seo-preview').screenshot({path: 'var/seo-preview.png'});
    for (const width of [320, 768, 1440]) {
        await page.setViewportSize({width, height: 900});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('SEO overflow at ' + width);
    }
    await page.goto(base + '/');
    for (const width of [320, 768, 960, 1024, 1440]) {
        await page.setViewportSize({width, height: 900});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Site navigation overflow at ' + width);
    }
    await page.getByRole('link', {name: 'Отзывы', exact: true}).click();
    if (new URL(page.url()).hash !== '#reviews' || !await page.getByRole('heading', {name: 'Отзывы гостей', exact: true}).isVisible()) throw new Error('Reviews navigation failed');
    if (!await page.getByText('Отзывов пока нет.', {exact: true}).isVisible()) throw new Error('Empty reviews section not visible');
    await page.waitForFunction(() => { const top = document.querySelector('#reviews').getBoundingClientRect().top; const bottom = document.querySelector('[data-site-header]').getBoundingClientRect().bottom; return top >= bottom && top <= bottom + 32; });
    await page.screenshot({path: 'var/reviews-visible-empty.png'});
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: SEO sections, live counters, retina preview, original palette, responsive layout and visible reviews navigation';
}
