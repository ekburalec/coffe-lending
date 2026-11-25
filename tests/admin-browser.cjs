async (page) => {
    const failures = [];
    page.on('pageerror', error => failures.push(error.message));
    page.on('response', response => { if (response.status() >= 400) failures.push(response.url()); });
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto('http://localhost:8090/admin');
    if (!await page.getByRole('heading', {name: 'Управление кофейней'}).isVisible()) throw new Error('Login failed');
    await page.screenshot({path: 'var/admin-desktop.png'});
    const sections = ['categories', 'products', 'contacts', 'hours', 'exceptions', 'delivery', 'navigation', 'content', 'images', 'faq', 'announcements', 'reviews', 'rating', 'seo'];
    for (const section of sections) {
        await page.goto('http://localhost:8090/admin/edit?section=' + section);
        if (await page.locator('h1').count() !== 1) throw new Error('Missing heading: ' + section);
    }
    await page.goto('http://localhost:8090/admin/edit?section=products&id=americano');
    for (const width of [320, 768, 1440]) {
        await page.setViewportSize({width, height: 900});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Admin overflow at ' + width);
    }
    const form = await page.locator('main').evaluate(el => {
        const inputs = [...el.querySelectorAll('input:not([type="hidden"]),textarea,select')];
        const ids = [...document.querySelectorAll('[id]')].map(x => x.id);
        return {labels: inputs.every(x => x.labels.length > 0), unique: new Set(ids).size === ids.length};
    });
    if (!form.labels || !form.unique) throw new Error('Admin form labels/IDs failed');
    await page.locator('#field-name').focus();
    if (!await page.locator('#field-name').evaluate(el => el.matches(':focus-visible'))) throw new Error('Focus is not visible');
    await page.setViewportSize({width: 320, height: 900});
    await page.screenshot({path: 'var/admin-mobile.png'});
    await page.goto('http://localhost:8090/admin/backups');
    if (!await page.getByRole('heading', {name: 'Восстановить копию', exact: true}).isVisible()) throw new Error('Backups missing');
    await page.getByRole('button', {name: 'Выйти', exact: true}).click();
    if (!await page.getByRole('heading', {name: 'Вход в админку'}).isVisible()) throw new Error('Logout failed');
    if (failures.length) throw new Error(failures.join('\n'));
    return 'PASS: admin session/logout, all editors, labels, focus, responsive widths, resources';
}
