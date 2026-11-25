async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.emulateMedia({reducedMotion: 'reduce'});
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(base + '/admin/edit?section=reviews');
    const ids = await page.locator('[id^="record-review-test-"]').evaluateAll(nodes => nodes.map(node => node.id.replace('record-', '')));
    if (ids.length !== 6) throw new Error('Use the isolated six-review fixture');

    async function toggle(id) {
        await page.goto(base + '/admin/edit?section=reviews');
        const row = page.locator('#record-' + id);
        await row.locator('button[value="toggle_visibility"]').click();
        await page.waitForLoadState('domcontentloaded');
    }

    try {
        const activeColor = await page.locator('.admin__nav-link[aria-current="page"]').evaluate(el => getComputedStyle(el).backgroundColor);
        if (activeColor !== 'rgb(139, 68, 45)') throw new Error('Original copper palette not preserved');
        const uniqueIds = await page.evaluate(() => { const ids = [...document.querySelectorAll('[id]')].map(x => x.id); return new Set(ids).size === ids.length; });
        if (!uniqueIds || !await page.locator('#rating-title').isVisible()) throw new Error('Review editor/rating structure failed');
        await page.screenshot({path: 'var/reviews-admin.png'});
        await page.goto(base + '/');
        const track = page.locator('[data-reviews-track]');
        const visibleColumns = () => track.evaluate(el => Math.round(el.clientWidth / el.firstElementChild.getBoundingClientRect().width));
        if (await visibleColumns() !== 3) throw new Error('Desktop must show three cards');
        await page.locator('[data-reviews-next]').click();
        await page.waitForFunction(() => document.querySelector('[data-reviews-track]').scrollLeft > 0);
        await page.locator('[data-reviews-previous]').click();
        await page.waitForFunction(() => document.querySelector('[data-reviews-track]').scrollLeft < 2);
        await page.locator('#reviews').scrollIntoViewIfNeeded();
        await page.screenshot({path: 'var/reviews-site.png'});
        for (const [width, columns] of [[768, 2], [320, 1]]) {
            await page.setViewportSize({width, height: 900});
            if (await visibleColumns() !== columns) throw new Error('Review columns at ' + width);
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Page overflow at ' + width);
        }
        const noJs = await page.context().browser().newContext({javaScriptEnabled: false, viewport: {width: 1440, height: 1000}});
        const plain = await noJs.newPage();
        await plain.goto(base + '/');
        if (await plain.locator('.reviews__card').count() !== 6 || !await plain.locator('[data-reviews-controls]').isHidden()) throw new Error('No-JS reviews failed');
        await noJs.close();
        await toggle(ids[0]);
        if (!await page.locator('#record-' + ids[0]).getByText('Скрыто', {exact: true}).isVisible()) throw new Error('Hidden badge missing');
        await page.goto(base + '/');
        if (await page.locator('.reviews__card').count() !== 5) throw new Error('Hidden review still public');
        await toggle(ids[0]);
        for (const id of ids) await toggle(id);
        await page.goto(base + '/');
        if (await page.locator('#reviews').count() || await page.locator('a[href="#reviews"]').count()) throw new Error('Empty reviews section/link not hidden');
        await page.goto(base + '/admin/edit?section=faq');
        if (!await page.getByRole('heading', {name: 'Добавить вопрос', exact: true}).count()) throw new Error('Inline FAQ form missing');
        if (await page.getByRole('button', {name: /^(Выше|Ниже)$/}).count()) throw new Error('Legacy move buttons present');
        if (errors.length) throw new Error(errors.join('\n'));
        return 'PASS: original palette, review editor, 3/2/1 cards, carousel, no-JS, visibility and empty-state navigation';
    } finally {
        await page.goto(base + '/admin/edit?section=reviews');
        for (const id of ids) {
            if (await page.locator('#record-' + id).getByText('Скрыто', {exact: true}).count()) await toggle(id);
        }
        await page.setViewportSize({width: 1440, height: 1000});
    }
}
