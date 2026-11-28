async page => {
    const base = new URL(page.url()).origin;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(base + '/admin');
    await page.getByRole('navigation', {name: 'Разделы управления'}).getByRole('link', {name: 'Пароль администратора', exact: true}).click();
    if (new URL(page.url()).pathname !== '/admin/password') throw new Error('Password menu route');
    if (await page.locator('.admin__nav-link[aria-current="page"]').innerText() !== 'Пароль администратора') throw new Error('Active password menu');
    const current = page.getByLabel('Текущий пароль', {exact: true});
    const next = page.getByLabel('Новый пароль', {exact: true});
    const confirmation = page.getByLabel('Повторите новый пароль', {exact: true});
    if (await current.getAttribute('autocomplete') !== 'current-password' || await next.getAttribute('autocomplete') !== 'new-password' || await confirmation.getAttribute('autocomplete') !== 'new-password') throw new Error('Password manager hints');
    await current.focus();
    await page.keyboard.press('Tab');
    if (!await current.locator('..').getByRole('button').evaluate(el => el === document.activeElement)) throw new Error('Password eye focus order');
    await page.keyboard.press('Tab');
    if (!await next.evaluate(el => el === document.activeElement)) throw new Error('Keyboard focus order');
    await current.fill('intentionally-wrong-current-password');
    const temporary = 'qa-' + Date.now() + '-input';
    await next.fill(temporary);
    await confirmation.fill(temporary);
    const failure = page.waitForResponse(response => response.url() === base + '/admin/password' && response.request().method() === 'POST');
    await page.getByRole('button', {name: 'Изменить пароль', exact: true}).click();
    if ((await failure).status() !== 422) throw new Error('Wrong current password was not rejected');
    if (await current.getAttribute('aria-invalid') !== 'true' || !(await page.locator('#current-password-error').innerText()).includes('неверно')) throw new Error('Field error missing');
    if (await current.inputValue() || await next.inputValue() || await confirmation.inputValue()) throw new Error('Passwords must not be echoed');
    for (const width of [1440, 768, 320]) {
        await page.setViewportSize({width, height: 1000});
        if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Password page overflow at ' + width);
    }
    await page.screenshot({path: 'var/admin-password-320.png', fullPage: true});
    await page.setViewportSize({width: 1440, height: 1000});
    await page.goto(base + '/admin/password');
    await page.screenshot({path: 'var/admin-password-desktop.png', fullPage: true});
    if (errors.length) throw new Error(errors.join('\n'));
    return 'PASS: password menu, labels/autocomplete, keyboard, field error, no echoed passwords and 320/768/1440 layout';
}
