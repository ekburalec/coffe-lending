<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/core/bootstrap.php';
$applicationLock = applicationLock(true);
$pdo = database();
$pdo->beginTransaction();
try {
    $pdo->exec(file_get_contents(APP_ROOT . '/config/schema.sql'));
    $seed = json_decode(file_get_contents(APP_ROOT . '/config/seed.json'), true, 512, JSON_THROW_ON_ERROR);
    if (!$pdo->query('SELECT COUNT(*) FROM schema_version')->fetchColumn()) {
        foreach ($seed['records'] as $kind => $records) {
            foreach ($records as $record) saveRecord($kind, $record['id'], $record);
        }
        foreach ($seed['settings'] as $name => $data) saveSettings($name, $data);
        $pdo->exec('INSERT INTO schema_version VALUES(1)');
    }
    // Дополняем только отсутствующие фиксированные поля при обновлении приложения.
    // Товары, настройки и уже отредактированные тексты никогда не пересеиваются.
    foreach (['content', 'images', 'hours'] as $kind) {
        foreach ($seed['records'][$kind] as $record) {
            if (!findRecord($kind, $record['id'])) saveRecord($kind, $record['id'], $record);
        }
    }
    if (!$pdo->query('SELECT COUNT(*) FROM schema_version WHERE version = 2')->fetchColumn()) {
        $links = listRecords('navigation');
        $reviewLink = null;
        foreach ($links as $link) if ($link['target'] === '#reviews') { $reviewLink = $link; break; }
        $reviewLink ??= ['id' => 'nav-reviews', 'target' => '#reviews', 'sort_order' => $links ? max(array_column($links, 'sort_order')) + 1 : 0];
        $reviewLink['name'] = 'Отзывы';
        $reviewLink['visible'] = 1;
        saveRecord('navigation', $reviewLink['id'], $reviewLink);
        $pdo->exec('INSERT INTO schema_version VALUES(2)');
    }
    $pdo->exec(file_get_contents(APP_ROOT . '/config/orders-schema.sql'));
    if (!$pdo->query('SELECT COUNT(*) FROM schema_version WHERE version=3')->fetchColumn()) {
        foreach (['beans' => 'Кофе в зёрнах', 'merch' => 'Мерч'] as $id => $name) {
            if (!findRecord('categories', $id)) saveRecord('categories', $id, ['name' => $name, 'sort_order' => 100, 'visible' => 1]);
        }
        $textUpdates = [
            'copy-13' => ['Выбирайте позиции и смотрите итоговую сумму. Состав заказа не отправляется в кофейню.', 'Выберите товары, проверьте сумму и перейдите к оформлению заказа.'],
            'copy-38' => ['Время указано ориентировочно. На сайте доступен только просмотр состава заказа, без отправки и оплаты.', 'Время указано ориентировочно. Состав, адрес и получение подтвердит менеджер. Онлайн-оплаты нет.'],
        ];
        foreach ($textUpdates as $id => [$old, $new]) {
            $record = findRecord('content', $id);
            if ($record && $record['text'] === $old) { $record['text'] = $new; $record['label'] = $new; saveRecord('content', $id, $record); }
        }
        $faq = findRecord('faq', 'faq-5');
        if ($faq && str_starts_with($faq['answer'], 'Нет. Здесь можно выбрать позиции')) {
            $faq['question'] = 'Как оформить заказ и оплатить?';
            $faq['answer'] = 'Выберите товары и получение, заполните контакты и оформите заказ. Менеджер подтвердит детали и согласует способ расчёта. Онлайн-оплаты нет.';
            saveRecord('faq', 'faq-5', $faq);
        }
        $delivery = getSettings('delivery');
        if (($delivery['fee'] ?? null) === 150 && ($delivery['zone'] ?? '') === 'В радиусе 3 км от кофейни') {
            $delivery['fee'] = 200;
            $delivery['zone'] = 'Екатеринбург. Адрес подтвердит менеджер';
            saveSettings('delivery', $delivery);
        }
        $pdo->exec('INSERT INTO schema_version VALUES(3)');
    }
    $pdo->commit();
    echo "Миграции выполнены. Существующие данные сохранены.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
