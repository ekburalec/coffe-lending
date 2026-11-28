<?php
declare(strict_types=1);

function database(): PDO
{
    global $appDatabase;
    $pdo = $appDatabase ?? null;
    if ($pdo) return $pdo;
    $directory = appConfig('data_dir');
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create data directory');
    }
    $pdo = new PDO('sqlite:' . $directory . '/site.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000; PRAGMA secure_delete = ON;');
    $appDatabase = $pdo;
    return $pdo;
}

function closeDatabase(): void
{
    global $appDatabase;
    $appDatabase = null;
}

function applicationLock(bool $exclusive): mixed
{
    $directory = appConfig('data_dir');
    if (!is_dir($directory)) mkdir($directory, 0700, true);
    $handle = fopen($directory . '/application.lock', 'c');
    if (!$handle || !flock($handle, $exclusive ? LOCK_EX : LOCK_SH)) throw new RuntimeException('Cannot acquire application lock');
    return $handle;
}
