<?php
declare(strict_types=1);

function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $directory = appConfig('data_dir') . '/sessions';
    if (!is_dir($directory)) mkdir($directory, 0700, true);
    session_save_path($directory);
    session_name('coffee_admin');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Strict', 'path' => '/admin']);
    session_start();
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    if (isset($_SESSION['admin']) && ($_SESSION['last_seen'] ?? 0) < time() - appConfig('session_timeout')) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = time();
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function requireAdmin(): void
{
    startAdminSession();
    if (!isset($_SESSION['admin'])) redirect('/admin/login');
}

function validCsrf(): bool
{
    return hash_equals($_SESSION['csrf'] ?? '', postText('csrf')) && postText('csrf') !== '';
}

function adminPage(string $title, string $view, array $data = []): void
{
    render('admin/layout', array_merge($data, ['title' => $title, 'view' => $view, 'csrf' => $_SESSION['csrf']]));
}
