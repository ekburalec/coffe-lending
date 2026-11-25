async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const check = (actual, expected) => { if (actual.join(',') !== expected.join(',')) throw new Error('Unexpected order: ' + actual); };
    for (const section of ['navigation', 'faq']) {
        await page.setViewportSize({width: 1440, height: 1000});
        await page.goto(base + '/admin/edit?section=' + section);
        const list = page.locator('[data-reorder-section="' + section + '"]');
        const read = () => list.locator('[data-reorder-id]').evaluateAll(nodes => nodes.map(node => node.dataset.reorderId));
        const initial = await read();
        if (initial.length < 2) throw new Error('Fixture needs two records');
        const response = () => page.waitForResponse(res => res.url() === base + '/admin/records/reorder' && res.request().method() === 'POST');
        const settled = () => page.waitForFunction(() => document.querySelector('[data-reorder-list]').getAttribute('aria-busy') === 'false');
        await list.locator('[data-reorder-handle]').first().scrollIntoViewIfNeeded();
        const handle = await list.locator('[data-reorder-handle]').first().boundingBox();
        const second = await list.locator('[data-reorder-id]').nth(1).boundingBox();
        await page.mouse.move(handle.x + handle.width / 2, handle.y + handle.height / 2);
        await page.mouse.down();
        await page.mouse.move(handle.x + handle.width / 2, second.y + second.height - 8, {steps: 12});
        const saved = response();
        await page.mouse.up();
        if ((await saved).status() !== 200) throw new Error('Mouse save failed');
        await settled();
        const moved = [initial[1], initial[0], ...initial.slice(2)];
        check(await read(), moved);
        await page.reload();
        check(await read(), moved);
        await list.locator('[data-reorder-handle]').first().focus();
        await page.keyboard.press('Enter');
        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('Escape');
        check(await read(), moved);
        await page.route(base + '/admin/records/reorder', route => route.fulfill({status: 503, contentType: 'application/json', body: JSON.stringify({message: 'Тест ошибки сохранения'})}));
        await list.locator('[data-reorder-handle]').first().focus();
        await page.keyboard.press('Enter');
        await page.keyboard.press('ArrowDown');
        const failed = response();
        await page.keyboard.press('Enter');
        if ((await failed).status() !== 503) throw new Error('Failure fixture');
        await settled();
        check(await read(), moved);
        if (!await page.getByRole('alert').last().isVisible()) throw new Error('Error not announced');
        await page.unroute(base + '/admin/records/reorder');
        await list.locator('[data-reorder-handle]').first().focus();
        await page.keyboard.press('Enter');
        await page.keyboard.press('ArrowDown');
        const restored = response();
        await page.keyboard.press('Enter');
        if ((await restored).status() !== 200) throw new Error('Keyboard save');
        await settled();
        check(await read(), initial);
        for (const width of [1440, 768, 320]) {
            await page.setViewportSize({width, height: 1000});
            if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error(section + ' overflow at ' + width);
        }
        await page.screenshot({path: 'var/reorder-' + section + '-mobile.png'});
    }
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: navigation/FAQ mouse, keyboard, reload, Escape, 503 rollback and 320/768/1440 layouts';
}
