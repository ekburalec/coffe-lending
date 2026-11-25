async (page) => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(base + '/admin/edit?section=products');
    const list = page.locator('[data-reorder-category="coffee"]');
    const readOrder = async (target = list) => target.locator('[data-reorder-id]').evaluateAll(nodes => nodes.map(node => node.dataset.reorderId));
    const initial = await readOrder();
    const settled = async (targetPage = page) => targetPage.waitForFunction(() => document.querySelector('[data-reorder-category="coffee"]').getAttribute('aria-busy') === 'false');
    const assertOrder = (actual, expected, message) => { if (actual.join(',') !== expected.join(',')) throw new Error(message + ': ' + actual); };
    const response = () => page.waitForResponse(res => res.url() === base + '/admin/products/reorder' && res.request().method() === 'POST');
    let touchContext;
    try {
        if (initial.length < 3) throw new Error('Test needs three coffee products');
        await list.scrollIntoViewIfNeeded();
        const handle = await list.locator('[data-reorder-handle]').first().boundingBox();
        const last = await list.locator('[data-reorder-id]').last().boundingBox();
        await page.mouse.move(handle.x + handle.width / 2, handle.y + handle.height / 2);
        await page.mouse.down();
        await page.mouse.move(handle.x + handle.width / 2, last.y + last.height - 8, {steps: 16});
        const saved = response();
        await page.mouse.up();
        if ((await saved).status() !== 200) throw new Error('Mouse reorder rejected');
        await settled();
        const mouseOrder = initial.slice(1).concat(initial[0]);
        assertOrder(await readOrder(), mouseOrder, 'Mouse reorder');
        await page.reload();
        assertOrder(await readOrder(), mouseOrder, 'Order did not persist after reload');

        const keyboardHandle = list.locator('[data-reorder-handle]').first();
        await keyboardHandle.focus();
        await page.keyboard.press('Enter');
        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('Escape');
        assertOrder(await readOrder(), mouseOrder, 'Escape failed to restore order');
        await keyboardHandle.focus();
        await page.keyboard.press('Enter');
        await page.keyboard.press('ArrowDown');
        const keyboardSave = response();
        await page.keyboard.press('Enter');
        if ((await keyboardSave).status() !== 200) throw new Error('Keyboard reorder rejected');
        await settled();
        const keyboardOrder = [mouseOrder[1], mouseOrder[0], ...mouseOrder.slice(2)];
        assertOrder(await readOrder(), keyboardOrder, 'Keyboard reorder');

        await page.route(base + '/admin/products/reorder', route => route.fulfill({status: 503, contentType: 'application/json', body: JSON.stringify({message: 'Тест: сервер временно недоступен.'})}));
        await list.locator('[data-reorder-handle]').first().focus();
        await page.keyboard.press('Enter');
        await page.keyboard.press('ArrowDown');
        const failedSave = response();
        await page.keyboard.press('Enter');
        if ((await failedSave).status() !== 503) throw new Error('Failure fixture not used');
        await settled();
        assertOrder(await readOrder(), keyboardOrder, 'Failed save did not roll back');
        if (!(await page.getByRole('alert').last().innerText()).includes('временно недоступен')) throw new Error('Save error not announced');
        await page.unroute(base + '/admin/products/reorder');
        await page.screenshot({path: 'var/reorder-desktop.png'});

        touchContext = await page.context().browser().newContext({viewport: {width: 390, height: 844}, isMobile: true, hasTouch: true, storageState: await page.context().storageState()});
        const touchPage = await touchContext.newPage();
        touchPage.on('pageerror', error => errors.push(error.message));
        await touchPage.goto(base + '/admin/edit?section=products');
        const touchList = touchPage.locator('[data-reorder-category="coffee"]');
        await touchList.scrollIntoViewIfNeeded();
        const firstBox = await touchList.locator('[data-reorder-handle]').first().boundingBox();
        const secondBox = await touchList.locator('[data-reorder-id]').nth(1).boundingBox();
        const cdp = await touchContext.newCDPSession(touchPage);
        const x = firstBox.x + firstBox.width / 2;
        const startY = firstBox.y + firstBox.height / 2;
        const endY = secondBox.y + secondBox.height - 8;
        await cdp.send('Input.dispatchTouchEvent', {type: 'touchStart', touchPoints: [{x, y: startY}]});
        for (let step = 1; step <= 12; step++) await cdp.send('Input.dispatchTouchEvent', {type: 'touchMove', touchPoints: [{x, y: startY + (endY - startY) * step / 12}]});
        const touchSave = touchPage.waitForResponse(res => res.url() === base + '/admin/products/reorder');
        await cdp.send('Input.dispatchTouchEvent', {type: 'touchEnd', touchPoints: []});
        if ((await touchSave).status() !== 200) throw new Error('Touch reorder rejected');
        await settled(touchPage);
        assertOrder(await readOrder(touchList), [keyboardOrder[1], keyboardOrder[0], ...keyboardOrder.slice(2)], 'Touch reorder');
        if (await touchPage.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Mobile overflow');
        await touchPage.screenshot({path: 'var/reorder-mobile.png'});
        if (errors.length) throw new Error(errors.join('\n'));
        return 'PASS: mouse, persistence, keyboard, Escape, failed-save rollback, touch and mobile layout';
    } finally {
        if (touchContext) await touchContext.close();
        await page.unroute(base + '/admin/products/reorder');
        await page.goto(base + '/admin/edit?section=products');
        const restored = await page.evaluate(async order => {
            const root = document.querySelector('[data-reorder-category="coffee"]');
            const response = await fetch(root.dataset.reorderUrl, {method: 'POST', body: new URLSearchParams({csrf: root.dataset.reorderCsrf, category: 'coffee', version: root.dataset.reorderVersion, ids: JSON.stringify(order)})});
            return response.status;
        }, initial);
        if (restored !== 200) throw new Error('Failed to restore test fixture order');
        await page.reload();
    }
}
