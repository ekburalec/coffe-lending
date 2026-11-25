<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/core/bootstrap.php';
$applicationLock = applicationLock(true);
$records = json_decode(file_get_contents(APP_ROOT . '/config/demo-reviews.json'), true, 512, JSON_THROW_ON_ERROR);
$fields = adminSections()['reviews']['fields'];
foreach ($records as $record) {
    [, $errors] = validateAdminFields($fields, [], $record + ['sort_order' => 0], false);
    if (($record['is_demo'] ?? 0) !== 1 || $errors || validateAdminRecord('reviews', $record)) throw new RuntimeException('Некорректный демо-отзыв.');
}
$pdo = database();
$pdo->beginTransaction();
try {
    $positions = array_column(listRecords('reviews'), 'sort_order');
    $next = $positions ? max($positions) + 1 : 0;
    $added = 0;
    foreach ($records as $record) {
        if (findRecord('reviews', $record['id'])) continue;
        $record['sort_order'] = $next++;
        saveRecord('reviews', $record['id'], $record);
        $added++;
    }
    $pdo->commit();
    echo 'Добавлено демо-отзывов: ' . $added . ". Существующие записи сохранены.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
