async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(base + '/admin/edit?section=products');
    const list = page.locator('[data-reorder-category="coffee"]');
    const read = () => list.locator('[data-reorder-id]').evaluateAll(nodes => nodes.map(node => node.dataset.reorderId));
    const initial = await read();
    let requests = 0;
    await page.route('**/admin/products/reorder', async route => {
        requests++;
        await route.fulfill({status: 200, contentType: 'application/json', body: JSON.stringify({version: 'motion-test'})});
    });
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    const begin = async () => {
        await list.scrollIntoViewIfNeeded();
        const handle = await list.locator('[data-reorder-handle]').first().boundingBox();
        const row = await list.locator('[data-reorder-id]').first().boundingBox();
        const point = {x: handle.x + handle.width / 2, y: handle.y + handle.height / 2};
        await page.mouse.move(point.x, point.y);
        await page.mouse.down();
        return {point, row};
    };
    const cleaned = () => page.waitForFunction(() => !document.querySelector('.is-drag-preview, .is-placeholder'));
    try {
        const {point, row} = await begin();
        await page.mouse.move(point.x + 2, point.y + 1);
        check(await page.locator('.is-drag-preview').count() === 0, 'A click must not lift the row');
        await page.mouse.move(point.x + 24, point.y + 18);
        const preview = page.locator('.is-drag-preview');
        const floating = await preview.boundingBox();
        check(Math.abs(floating.x - row.x - 24) < 2 && Math.abs(floating.y - row.y - 18) < 2, 'Preview must keep the grab offset on both axes: ' + JSON.stringify({floating, row}));
        check(await preview.getAttribute('aria-hidden') === 'true', 'Preview must be hidden from accessibility');
        check(await preview.locator('[id], [data-reorder-id], [data-reorder-handle]').count() === 0, 'Preview must not duplicate IDs or hooks');
        await page.screenshot({path: 'var/reorder-motion-lift.png'});
        const last = await list.locator('[data-reorder-id]').last().boundingBox();
        await page.mouse.move(point.x, last.y + last.height - 8, {steps: 12});
        check((await read()).join() === initial.slice(1).concat(initial[0]).join(), 'Placeholder must move to the last slot');
        await page.waitForTimeout(300);
        check((await read()).join() === initial.slice(1).concat(initial[0]).join(), 'Stationary pointer must not cause oscillation');
        await page.keyboard.press('Escape');
        await page.mouse.up();
        await cleaned();
        check((await read()).join() === initial.join() && requests === 0, 'Escape must restore without saving');

        const secondDrag = await begin();
        await page.mouse.move(secondDrag.point.x, last.y + last.height - 8, {steps: 8});
        await page.evaluate(() => {
            document.addEventListener('pointerup', () => {
                window.reorderDropObserved = !!document.querySelector('.is-drag-preview');
            }, {once: true});
        });
        await page.mouse.up();
        check(await page.evaluate(() => window.reorderDropObserved), 'Drop must keep the preview during landing');
        await cleaned();
        await page.waitForFunction(() => document.querySelector('[data-reorder-category="coffee"]').getAttribute('aria-busy') === 'false');
        check(requests === 1, 'Drop must save exactly once');

        await page.reload();
        await page.emulateMedia({reducedMotion: 'reduce'});
        const reduced = await begin();
        await page.mouse.move(reduced.point.x, last.y + last.height - 8, {steps: 8});
        check(await list.locator('[data-reorder-id]').evaluateAll(nodes => nodes.every(node => node.getAnimations().length === 0)), 'Reduced motion must skip neighbour animations');
        await page.keyboard.press('Escape');
        await page.mouse.up();
        await cleaned();

        await page.emulateMedia({reducedMotion: 'no-preference'});
        await page.setViewportSize({width: 320, height: 700});
        await page.reload();
        const mobile = await begin();
        await page.mouse.move(mobile.point.x + 8, mobile.point.y + 10);
        check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), '320px layout must not overflow');
        await page.screenshot({path: 'var/reorder-motion-320.png'});
        await page.mouse.move(1, 1);
        await page.mouse.up();
        await cleaned();
        check((await read()).join() === initial.join() && requests === 1, 'Outside drop must cancel');
        check(errors.length === 0, errors.join('\n'));
        return 'PASS: threshold, cursor attachment, placeholder, cancellation, single save, reduced motion, 320px, outside drop';
    } finally {
        await page.mouse.up();
        await page.emulateMedia({reducedMotion: 'no-preference'});
        await page.unroute('**/admin/products/reorder');
    }
}
