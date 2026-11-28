async page => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const plain = await page.context().browser().newContext({javaScriptEnabled: false});
    const checkout = await plain.newPage();
    await checkout.goto(base + '/checkout');
    await checkout.locator('#qty-americano').fill('1');
    await checkout.getByRole('button', {name: 'Пересчитать состав'}).click();
    await checkout.getByLabel('Имя', {exact: true}).fill('Без JavaScript');
    await checkout.getByLabel('Телефон', {exact: true}).fill('+79001234567');
    await checkout.locator('#consent').check();
    await checkout.getByRole('button', {name: 'Оформить заказ', exact: true}).click();
    const heading = await checkout.locator('h1').innerText();
    if (!heading.includes('получен')) throw new Error('No-JS checkout');
    const id = Number(heading.match(/VP-([0-9]+)/)[1]);
    await plain.close();
    await page.goto(base + '/admin/login');
    if (await page.getByLabel('Логин', {exact: true}).count()) {
        await page.getByLabel('Логин', {exact: true}).fill('ordertest');
        await page.getByLabel('Пароль', {exact: true}).fill('QA-orders-L9!temporary-G7!only');
        await page.getByRole('button', {name: 'Войти', exact: true}).click();
    }
    await page.goto(base + '/admin/orders?board=1&receiving=pickup');
    await page.locator(`[data-order-card="${id}"]`).dragTo(page.locator('[data-order-column="contacted"]'));
    await page.waitForTimeout(500);
    await page.goto(base + '/admin/orders?id=' + id);
    if (!(await page.locator('main').innerText()).includes('Связались с клиентом')) throw new Error('Drag failed');
    const semantics = await page.evaluate(() => {
        const fields = [...document.querySelectorAll('input:not([type=hidden]),textarea,select')];
        const ids = [...document.querySelectorAll('[id]')].map(node => node.id);
        return fields.every(input => input.labels.length) && new Set(ids).size === ids.length
            && [...document.querySelectorAll('[aria-describedby]')].every(input => input.getAttribute('aria-describedby').split(/\s+/).every(id => document.getElementById(id)));
    });
    if (!semantics) throw new Error('Labels/descriptions/unique IDs');
    for (const width of [320, 375, 768, 1440]) {
        await page.setViewportSize({width, height: 900});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Admin overflow ' + width);
    }
    await page.goto(base + '/admin/orders?board=1&receiving=pickup');
    await page.screenshot({path: 'var/orders-board-qa.png', fullPage: true});
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: no-JS checkout, kanban drag, labels/descriptions, responsive admin';
}
