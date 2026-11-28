<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit;
$required = ['pdo_sqlite', 'dom', 'mbstring', 'fileinfo', 'gd', 'zip', 'openssl'];
$missing = array_filter($required, fn (string $name): bool => !extension_loaded($name));
if (version_compare(PHP_VERSION, '8.2', '<') || $missing || !function_exists('imagewebp')) {
    fwrite(STDERR, 'Нужен PHP 8.2+ с WebP. Отсутствуют расширения: ' . implode(', ', $missing) . "\n");
    exit(1);
}
require dirname(__DIR__) . '/app/core/bootstrap.php';
$applicationLock = applicationLock(false);
echo 'PHP: ' . PHP_VERSION . "\nДанные: " . appConfig('data_dir') . "\nФото: " . appConfig('upload_dir') . "\n";
echo 'Миграция: ' . database()->query('SELECT MAX(version) FROM schema_version')->fetchColumn() . "\n";
$admin = adminAccount();
echo 'Администратор: ' . ($admin ? 'создан' : 'не создан; выполните bin/create-admin.php') . "\n";
if ($admin && !password_get_info($admin['password_hash'])['algo']) {
    fwrite(STDERR, "Алгоритм сохранённого пароля недоступен в этой сборке PHP. Проверьте поддержку Argon2id до переноса аккаунта.\n");
    exit(1);
}
echo 'Новые пароли: ' . (hasArgonPasswords() ? 'Argon2id' : 'bcrypt, предел 72 байта') . "; " . newPasswordHint() . "\n";
echo "Проверка окружения пройдена.\n";
