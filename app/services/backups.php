<?php
declare(strict_types=1);

function backupDirectory(): string
{
    $directory = appConfig('data_dir') . '/backups';
    if (!is_dir($directory)) mkdir($directory, 0700, true);
    return $directory;
}

function databaseSnapshot(string $destination): void
{
    $query = database()->prepare('VACUUM INTO ?');
    $query->execute([$destination]);
    chmod($destination, 0600);
}

function createBackup(bool $full): string
{
    $name = 'backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
    $snapshot = backupDirectory() . '/' . $name . '.sqlite';
    databaseSnapshot($snapshot);
    if (!$full) return $snapshot;
    if (!class_exists('ZipArchive')) {
        unlink($snapshot);
        throw new RuntimeException('Для полной копии требуется ZipArchive.');
    }
    $path = backupDirectory() . '/' . $name . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Не удалось создать архив.');
    try {
        $zip->addFile($snapshot, 'site.sqlite');
        $manifest = ['format' => 'coffee-backup', 'version' => 1, 'schema' => 1, 'files' => ['site.sqlite' => hash_file('sha256', $snapshot)]];
        foreach (glob(appConfig('upload_dir') . '/*') ?: [] as $file) {
            if (!is_file($file) || !preg_match('/^[a-f0-9]{32}(?:@2x)?\.(?:webp|jpg)$/D', basename($file))) continue;
            $entry = 'media/' . basename($file);
            $zip->addFile($file, $entry);
            $manifest['files'][$entry] = hash_file('sha256', $file);
        }
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        if (!$zip->close()) throw new RuntimeException('Не удалось завершить архив.');
        chmod($path, 0600);
    } finally {
        if (is_file($snapshot)) unlink($snapshot);
    }
    return $path;
}

function validateBackupDatabase(string $path, array $stagedMedia): void
{
    $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('PRAGMA query_only = ON;');
    $objects = $pdo->query("SELECT name, type, sql FROM sqlite_master WHERE name NOT LIKE 'sqlite_%'")->fetchAll();
    $tables = ['schema_version', 'records', 'settings', 'admins', 'login_attempts'];
    if (count($objects) !== count($tables)) throw new InvalidArgumentException('Неподдерживаемая структура БД.');
    foreach ($objects as $object) if ($object['type'] !== 'table' || !in_array($object['name'], $tables, true)) throw new InvalidArgumentException('Неподдерживаемые объекты БД.');
    $normalizeSql = fn (string $sql): string => str_replace('ifnotexists', '', strtolower(preg_replace('/\\s+/', '', $sql)));
    $definitions = [];
    foreach (explode(';', file_get_contents(APP_ROOT . '/config/schema.sql')) as $sql) {
        if (preg_match('/CREATE TABLE IF NOT EXISTS ([a-z_]+)/', $sql, $match)) $definitions[$match[1]] = $normalizeSql($sql);
    }
    foreach ($objects as $object) if ($normalizeSql($object['sql']) !== ($definitions[$object['name']] ?? '')) throw new InvalidArgumentException('Определения таблиц не соответствуют приложению.');
    if ($pdo->query('PRAGMA quick_check')->fetchColumn() !== 'ok') throw new InvalidArgumentException('Копия БД повреждена.');
    if ($pdo->query('SELECT version FROM schema_version')->fetchAll(PDO::FETCH_COLUMN) !== [1]) throw new InvalidArgumentException('Несовместимая версия схемы.');
    $admins = $pdo->query('SELECT * FROM admins')->fetchAll();
    if (count($admins) > 1 || ($admins && ($admins[0]['id'] != 1 || !preg_match('/^[a-zA-Z0-9_.-]{3,80}$/D', $admins[0]['username']) || !password_get_info($admins[0]['password_hash'])['algo']))) throw new InvalidArgumentException('Некорректная учётная запись в копии.');
    $collections = [];
    foreach ($pdo->query('SELECT * FROM records') as $row) {
        if (!preg_match('/^[a-z0-9-]{1,80}$/D', $row['id']) || !in_array((int) $row['visible'], [0, 1], true)) throw new InvalidArgumentException('Некорректная запись в копии.');
        $data = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) throw new InvalidArgumentException('Некорректные данные записи.');
        $collections[$row['kind']][] = array_merge($data, ['id' => $row['id'], 'sort_order' => $row['sort_order'], 'visible' => $row['visible']]);
    }
    // Наличие категорий/исключений в проверяемой БД не должно зависеть от текущей БД.
    $collections += ['categories' => [], 'exceptions' => []];
    $sections = adminSections($collections);
    foreach ($collections as $kind => $records) {
        if (!isset($sections[$kind]) || ($sections[$kind]['singleton'] ?? false)) throw new InvalidArgumentException('Неизвестная коллекция.');
        foreach ($records as $record) validateRestoredFields($kind, $sections[$kind], $record, $collections, $stagedMedia);
    }
    $groups = [];
    foreach ($pdo->query('SELECT * FROM settings') as $row) {
        $kind = $row['name'];
        if (!isset($sections[$kind]) || !($sections[$kind]['singleton'] ?? false)) throw new InvalidArgumentException('Неизвестные настройки.');
        $groups[] = $kind;
        validateRestoredFields($kind, $sections[$kind], json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR), $collections, $stagedMedia);
    }
    foreach (['contacts', 'delivery', 'seo', 'rating'] as $kind) if (!in_array($kind, $groups, true)) throw new InvalidArgumentException('В копии отсутствуют обязательные настройки.');
    $ids = array_column($collections['hours'] ?? [], 'id');
    sort($ids);
    if ($ids !== ['1','2','3','4','5','6','7']) throw new InvalidArgumentException('Неполное расписание.');
    $initial = json_decode(file_get_contents(APP_ROOT . '/config/seed.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach (['content', 'images'] as $kind) {
        foreach (array_column($initial['records'][$kind], 'id') as $id) {
            if (!in_array($id, array_column($collections[$kind] ?? [], 'id'), true)) throw new InvalidArgumentException('В копии отсутствуют обязательные поля страницы.');
        }
    }
}

function validateRestoredFields(string $kind, array $section, array $record, array $collections, array $stagedMedia): void
{
    $fields = $section['fields'];
    foreach ($fields as &$field) if (($field['type'] ?? '') === 'image') $field['type'] = 'text';
    unset($field);
    [, $errors] = validateAdminFields($fields, $record, $record, false);
    $errors = array_merge($errors, validateAdminRecord($kind, $record, $collections));
    if ($errors) throw new InvalidArgumentException('Некорректные поля в разделе «' . $section['title'] . '».');
    if (isset($record['image'])) {
        $image = $record['image'];
        if (!preg_match('~^assets/img/[a-zA-Z0-9_@/-]+\.(webp|jpg)$~D', $image)) throw new InvalidArgumentException('Некорректный путь фотографии.');
        if (str_starts_with($image, 'assets/img/uploads/')) {
            $file = $stagedMedia[basename($image)] ?? appConfig('upload_dir') . '/' . basename($image);
            if (!is_file($file)) throw new InvalidArgumentException('Не хватает фотографий. Восстановите полный архив.');
            if (str_ends_with($image, '.webp')) {
                $retina = str_replace('.webp', '@2x.webp', basename($image));
                if (!is_file($stagedMedia[$retina] ?? appConfig('upload_dir') . '/' . $retina)) throw new InvalidArgumentException('Не хватает retina-фотографии.');
            }
        } elseif (!is_file(APP_ROOT . '/' . $image)) throw new InvalidArgumentException('Фотография отсутствует в установленной версии сайта.');
    }
}

function stageBackupArchive(string $source, string $stage): array
{
    if (!class_exists('ZipArchive')) throw new RuntimeException('Для восстановления требуется ZipArchive.');
    $zip = new ZipArchive();
    if ($zip->open($source) !== true) throw new InvalidArgumentException('Не удалось прочитать ZIP.');
    $media = [];
    try {
        if ($zip->numFiles > 1000) throw new InvalidArgumentException('В архиве слишком много файлов.');
        $total = 0;
        $names = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = $stat['name'];
            if (!preg_match('~^(?:manifest\.json|site\.sqlite|media/[a-f0-9]{32}(?:@2x)?\.(?:webp|jpg))$~D', $name) || isset($names[$name])) throw new InvalidArgumentException('Недопустимый путь или повтор файла в архиве.');
            $names[$name] = true;
            $total += $stat['size'];
            if ($total > 512 * 1024 * 1024 || $stat['size'] > 64 * 1024 * 1024) throw new InvalidArgumentException('Распакованный архив слишком большой.');
        }
        $manifestText = $zip->getFromName('manifest.json', 1024 * 1024);
        $manifest = json_decode($manifestText ?: '', true, 512, JSON_THROW_ON_ERROR);
        if (($manifest['format'] ?? '') !== 'coffee-backup' || ($manifest['version'] ?? 0) !== 1 || ($manifest['schema'] ?? 0) !== 1 || !isset($manifest['files']['site.sqlite'])) throw new InvalidArgumentException('Несовместимый формат копии.');
        if (count($manifest['files']) !== count($names) - 1) throw new InvalidArgumentException('Состав архива не совпадает с манифестом.');
        foreach ($manifest['files'] as $name => $hash) {
            if (!isset($names[$name]) || $name === 'manifest.json') throw new InvalidArgumentException('Неверный манифест.');
            $bytes = $zip->getFromName($name);
            if ($bytes === false || !hash_equals((string) $hash, hash('sha256', $bytes))) throw new InvalidArgumentException('Контрольная сумма файла не совпадает.');
            $destination = $stage . '/' . basename($name);
            if (file_put_contents($destination, $bytes) !== strlen($bytes)) throw new RuntimeException('Недостаточно места для восстановления.');
            if (str_starts_with($name, 'media/')) {
                $image = getimagesize($destination);
                $expected = str_ends_with($name, '.jpg') ? 'image/jpeg' : 'image/webp';
                if (!$image || $image['mime'] !== $expected || $image[0] * $image[1] > 20000000) throw new InvalidArgumentException('Некорректная фотография в архиве.');
                $media[basename($name)] = $destination;
            }
        }
    } finally { $zip->close(); }
    return $media;
}

function restoreBackup(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || ($file['size'] ?? 0) > 200 * 1024 * 1024) throw new InvalidArgumentException('Загрузите копию до 200 МБ (лимит PHP может быть меньше).');
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['sqlite', 'zip'], true)) throw new InvalidArgumentException('Нужен файл .sqlite или .zip.');
    $stage = appConfig('data_dir') . '/restore-' . bin2hex(random_bytes(8));
    mkdir($stage, 0700);
    try {
        $media = [];
        if ($extension === 'zip') $media = stageBackupArchive($file['tmp_name'], $stage);
        elseif (!copy($file['tmp_name'], $stage . '/site.sqlite')) throw new RuntimeException('Не удалось подготовить БД.');
        validateBackupDatabase($stage . '/site.sqlite', $media);
        // Все изменения защищены эксклюзивным application.lock в index.php.
        $safety = createBackup(true);
        if (!is_dir(appConfig('upload_dir'))) mkdir(appConfig('upload_dir'), 0755, true);
        foreach ($media as $name => $source) {
            $destination = appConfig('upload_dir') . '/' . $name;
            if (is_file($destination) && hash_file('sha256', $destination) !== hash_file('sha256', $source)) throw new InvalidArgumentException('Конфликт фотографий с одинаковым именем.');
            if (!is_file($destination) && !copy($source, $destination)) throw new RuntimeException('Не удалось восстановить фотографию.');
        }
        closeDatabase();
        $live = appConfig('data_dir') . '/site.sqlite';
        $previous = $stage . '/previous.sqlite';
        if (!rename($live, $previous)) throw new RuntimeException('Не удалось подготовить замену БД.');
        if (!rename($stage . '/site.sqlite', $live)) {
            rename($previous, $live);
            throw new RuntimeException('БД не заменена; прежняя версия сохранена.');
        }
        chmod($live, 0600);
        $_SESSION = [];
        session_destroy();
        foreach (glob(appConfig('data_dir') . '/sessions/sess_*') ?: [] as $sessionFile) unlink($sessionFile);
        return $safety;
    } finally {
        foreach (glob($stage . '/*') ?: [] as $temporary) if (is_file($temporary)) unlink($temporary);
        rmdir($stage);
    }
}
