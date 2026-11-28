async (page) => {
    const base = new URL(page.url()).origin;
    await page.goto(base + '/admin/visual-editor');
    const heading = page.locator('[data-visual-text="copy-1"]');
    await page.waitForFunction(() => document.querySelector('[data-visual-text="copy-1"]').isContentEditable);
    await heading.fill('Локальная правка');
    const peer = await page.context().newPage();
    await peer.goto(base + '/admin/visual-editor');
    await peer.waitForFunction(() => document.querySelector('[data-visual-text="copy-1"]').isContentEditable);
    await peer.locator('[data-visual-text="copy-1"]').fill('Правка другой вкладки');
    await peer.locator('[data-visual-save]').click();
    await peer.waitForFunction(() => document.querySelector('[data-visual-message]').textContent === 'Изменения опубликованы.');
    await page.locator('[data-visual-save]').click();
    await page.waitForFunction(() => document.querySelector('[data-visual-message]').textContent.includes('другой вкладке'));
    if (await heading.innerText() !== 'Локальная правка') throw new Error('Conflict lost local draft');
    await page.locator('[data-visual-cancel]').click();
    await peer.close();
    await page.reload();
    await page.waitForFunction(() => document.querySelector('[data-visual-text="copy-1"]').isContentEditable);
    const freshHeading = page.locator('[data-visual-text="copy-1"]');
    await freshHeading.fill('Правка после повторного входа');
    // Выход из другого окна отзывает сессию; сам редактор сохраняет локальный ввод.
    const logout = await page.context().newPage();
    await logout.goto(base + '/admin');
    await logout.getByRole('button', {name: 'Выйти', exact: true}).click();
    await page.locator('[data-visual-save]').click();
    await page.waitForFunction(() => !document.querySelector('[data-visual-login]').hidden);
    if (await freshHeading.innerText() !== 'Правка после повторного входа') throw new Error('Expired session lost draft');
    await logout.close();
    return 'PASS: concurrent editors reject stale writes without losing draft; expired session retains draft and offers login';
}
