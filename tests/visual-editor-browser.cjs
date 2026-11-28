async (page) => {
    const base = new URL(page.url()).origin;
    const failures = [];
    page.on('pageerror', error => failures.push(error.message));
    page.on('response', response => {
        if (response.status() === 404 && response.url().includes('/assets/')) failures.push(response.url());
    });
    await page.setViewportSize({width: 1440, height: 1100});
    await page.goto(base + '/admin/visual-editor');
    const preview = page;
    const heading = preview.locator('[data-visual-text="copy-1"]');
    await heading.waitFor();
    await page.waitForFunction(() => document.querySelector('[data-visual-text="copy-1"]').isContentEditable);
    if (await page.locator('[data-visual-login]').isVisible() || await page.locator('[data-visual-message].is-error,.notify--error').count()) throw new Error('Initial preview showed a false error');
    const original = await heading.innerText();
    const oldPhoto = await preview.locator('[data-visual-image="image-0"]').getAttribute('src');
    if (new URL(page.url()).pathname !== '/' || await page.locator('iframe').count()) throw new Error('Editor must run on the site without a frame');
    if (!await page.locator('[data-site-header] [data-visual-editor]').count()) throw new Error('Editor toolbar must be at the top of the site');

    await heading.fill('Кофе для встречи');
    await heading.evaluate(element => {
        const range = document.createRange();
        range.selectNodeContents(element);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        document.execCommand('removeFormat');
    });
    await heading.press('Control+b');
    if (!await heading.locator('strong,b').count()) throw new Error('Keyboard bold failed');
    await page.locator('[data-visual-format="italic"]').click();
    if (!await heading.locator('em,i').count()) throw new Error('Toolbar italic failed');
    await page.locator('[data-visual-mode-toggle]').click();
    if (await heading.evaluate(element => element.isContentEditable)) throw new Error('Preview remained editable');
    await page.locator('[data-visual-mode-toggle]').click();
    if (!await heading.locator('strong,b').count()) throw new Error('Mode switch lost draft');
    await page.getByRole('button', {name: 'Отменить', exact: true}).click();
    if (await heading.innerText() !== original) throw new Error('Cancel failed');

    const occasion = preview.locator('[data-visual-text="copy-21"]');
    await occasion.fill('Утро с кофе');
    if (await preview.locator('[data-visual-mirror="copy-21"]').innerText() !== 'Утро с кофе') throw new Error('Shared title did not update');
    await preview.locator('[data-occasion-tab="tea"]').click();
    if (!await preview.locator('[data-visual-text="copy-24"]').isVisible()) throw new Error('Occasion tab blocked by editor');
    await page.getByRole('button', {name: 'Отменить', exact: true}).click();

    const picture = preview.locator('[data-visual-image="image-0"]');
    await picture.click();
    await page.locator('[data-visual-photo-alt]').fill('Напиток на тестовом фото');
    const bytes = await (await page.request.get(base + '/assets/img/og-image.jpg')).body();
    await page.locator('[data-visual-photo-file]').setInputFiles({name: 'photo.jpg', mimeType: 'image/jpeg', buffer: bytes});
    await page.waitForFunction(() => document.querySelector('[data-visual-photo-preview]').src.startsWith('blob:') && !document.querySelector('[data-visual-photo-form] [type="submit"]').disabled);
    await page.getByRole('button', {name: 'Применить к странице'}).click();
    if (!await picture.getAttribute('src').then(src => src.startsWith('blob:'))) throw new Error('Photo draft was not applied');
    await page.getByRole('button', {name: 'Отменить', exact: true}).click();
    if (await picture.getAttribute('src') !== oldPhoto) throw new Error('Photo cancel did not restore image');

    await heading.fill('Кофе для встречи');
    await heading.press('Control+a');
    await heading.evaluate(() => document.execCommand('removeFormat'));
    await heading.press('Control+b');
    await picture.click();
    await page.locator('[data-visual-photo-alt]').fill('Сохранённая фотография напитка');
    await page.locator('[data-visual-photo-file]').setInputFiles({name: 'photo.jpg', mimeType: 'image/jpeg', buffer: bytes});
    await page.waitForFunction(() => document.querySelector('[data-visual-photo-preview]').src.startsWith('blob:') && !document.querySelector('[data-visual-photo-form] [type="submit"]').disabled);
    await page.getByRole('button', {name: 'Применить к странице'}).click();
    await page.getByRole('button', {name: 'Сохранить', exact: true}).click();
    await page.waitForFunction(() => document.querySelector('[data-visual-message]').textContent === 'Изменения опубликованы.');
    if (!await page.getByRole('button', {name: 'Сохранить', exact: true}).isDisabled()) throw new Error('Save did not clear dirty state');
    const guestContext = await page.context().browser().newContext();
    const publicPage = await guestContext.newPage();
    await publicPage.goto(base + '/');
    if (!(await publicPage.locator('h1').innerText()).includes('Кофе для встречи')) throw new Error('Text not published');
    if (!await publicPage.locator('h1 strong').count()) throw new Error('Formatting not published');
    if (await publicPage.locator('[data-image-key="image-0"]').getAttribute('alt') !== 'Сохранённая фотография напитка') throw new Error('Alt not published');
    if (await publicPage.locator('[data-visual-editor],[data-visual-text]').count()) throw new Error('Editor leaked to visitor');
    await guestContext.close();
    await page.locator('[data-visual-mode-toggle]').click();
    await preview.locator('[data-order-add="americano"]').click();
    await preview.locator('[data-order-open]').first().click();
    if (!await preview.locator('[data-order-drawer]').evaluate(element => element.open)) throw new Error('Preview cart not available');
    await preview.locator('[data-order-close]').click();
    await page.locator('[data-visual-mode-toggle]').click();

    await heading.fill('Несохранённая правка');
    await page.getByRole('link', {name: 'Админка', exact: true}).click();
    if (!await page.locator('[data-visual-leave]').isVisible()) throw new Error('Leave guard missing');
    await page.getByRole('button', {name: 'Остаться', exact: true}).click();
    if (await heading.innerText() !== 'Несохранённая правка') throw new Error('Leave guard lost draft');
    const managed = preview.locator('[data-product="americano"]');
    if (await managed.evaluate(element => element.isContentEditable)) throw new Error('Product editable');
    await managed.hover();
    if (!await page.locator('[data-visual-managed-link]').isVisible()) throw new Error('Managed hint missing');
    await managed.click({position: {x: 20, y: 20}});
    if (!await page.locator('[data-visual-leave]').isVisible()) throw new Error('Managed link did not guard draft');
    await page.getByRole('button', {name: 'Остаться', exact: true}).click();
    await page.getByRole('button', {name: 'Отменить', exact: true}).click();

    for (const width of [320, 768, 1440]) {
        await page.setViewportSize({width, height: 1000});
        await page.evaluate(() => window.scrollTo({top: 0, behavior: 'instant'}));
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Shell overflow at ' + width);
        await page.waitForFunction(() => {
            const header = document.querySelector('[data-site-header]').getBoundingClientRect();
            const spacer = document.querySelector('[data-header-spacer]').getBoundingClientRect();
            return Math.abs(header.height - spacer.height) <= 1;
        });
        await page.screenshot({path: 'var/visual-editor-' + width + '.png', fullPage: false});
    }
    await page.setViewportSize({width: 1440, height: 1100});
    await heading.fill('Проверка потери сети');
    await page.route('**/admin/visual-editor/save', route => route.abort());
    await page.getByRole('button', {name: 'Сохранить', exact: true}).click();
    await page.waitForFunction(() => document.querySelector('[data-visual-message]').classList.contains('is-error'));
    if (await heading.innerText() !== 'Проверка потери сети') throw new Error('Network failure lost draft');
    await page.unroute('**/admin/visual-editor/save');
    await page.getByRole('button', {name: 'Отменить', exact: true}).click();
    const pageCount = page.context().pages().length;
    await page.getByRole('link', {name: 'Админка', exact: true}).click();
    await page.waitForURL(base + '/admin');
    await page.getByRole('link', {name: 'На сайт', exact: true}).click();
    await page.waitForURL(base + '/');
    if (page.context().pages().length !== pageCount) throw new Error('On-site editing opened an extra tab');
    await page.locator('[data-visual-editor]').waitFor();
    if (failures.length) throw new Error(failures.join('\n'));
    return 'PASS: inline editing, bold/italic, shared titles/tabs, modes, photo preview/cancel/save, publication, managed hints/links, leave guard, network failure, 320/768/1440 layouts';
}
