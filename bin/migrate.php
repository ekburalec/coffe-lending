<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/core/bootstrap.php';
$applicationLock = applicationLock(true);
$pdo = database();
$pdo->beginTransaction();
try {
    $pdo->exec(file_get_contents(APP_ROOT . '/config/schema.sql'));
    if (!$pdo->query('SELECT COUNT(*) FROM schema_version')->fetchColumn()) {
        $seed = json_decode(file_get_contents(APP_ROOT . '/config/seed.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($seed['records'] as $kind => $records) {
            foreach ($records as $record) saveRecord($kind, $record['id'], $record);
        }
        foreach ($seed['settings'] as $name => $data) saveSettings($name, $data);
        $pdo->exec('INSERT INTO schema_version VALUES(1)');
    }
    $pdo->commit();
    echo "Миграции выполнены. Существующие данные сохранены.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
