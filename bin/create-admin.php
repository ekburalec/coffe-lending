<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit;
require dirname(__DIR__) . '/app/core/bootstrap.php';
$username = trim($argv[1] ?? '');
$password = rtrim(stream_get_contents(STDIN), "\r\n");
if (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/D', $username) || strlen($password) < 12 || strlen($password) > 72) {
    fwrite(STDERR, "Передайте логин аргументом (3–80 символов), пароль через stdin (12–72 байта).\n");
    exit(1);
}
if (adminAccount() && !in_array('--reset', $argv, true)) {
    fwrite(STDERR, "Администратор уже существует. Для сброса нужен --reset.\n");
    exit(1);
}
$query = database()->prepare('INSERT INTO admins VALUES(1, ?, ?) ON CONFLICT(id) DO UPDATE SET username=excluded.username, password_hash=excluded.password_hash');
$query->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
// Сброс пароля отзывает сохранённые сессии.
foreach (glob(appConfig('data_dir') . '/sessions/sess_*') ?: [] as $sessionFile) unlink($sessionFile);
echo "Администратор сохранён.\n";
