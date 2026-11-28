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
    $admin = isset($_SESSION['admin']) ? adminAccount() : null;
    $passwordVersion = $admin ? hash('sha256', $admin['password_hash']) : '';
    if (isset($_SESSION['admin']) && (!$admin || !hash_equals($passwordVersion, $_SESSION['password_version'] ?? '') || ($_SESSION['last_seen'] ?? 0) < time() - appConfig('session_timeout'))) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_seen'] = time();
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/** Проверяет текущий пароль, сохраняет новый хеш и отзывает остальные сессии. */
function changeAdminPassword(#[SensitiveParameter] array $input): array
{
    $key = hash('sha256', 'password-change:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    if (loginBlocked($key)) return [['_form' => 'Слишком много попыток. Повторите через 15 минут.'], 429];
    $admin = adminAccount();
    $current = $input['current_password'] ?? null;
    if (!is_string($current) || strlen($current) > 72 || str_contains($current, "\0") || !password_verify($current, $admin['password_hash'])) {
        recordLoginFailure($key);
        return [['current_password' => 'Текущий пароль указан неверно.'], 422];
    }
    $password = $input['new_password'] ?? null;
    $confirmation = $input['confirm_password'] ?? null;
    $errors = [];
    if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
        $errors['new_password'] = 'Укажите пароль длиной от 12 до 72 байт без нулевого символа.';
    } elseif (password_verify($password, $admin['password_hash'])) {
        $errors['new_password'] = 'Новый пароль должен отличаться от текущего.';
    }
    if (!is_string($confirmation) || !is_string($password) || !hash_equals($password, $confirmation)) {
        $errors['confirm_password'] = 'Пароли не совпадают.';
    }
    if ($errors) return [$errors, 422];
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    if (!updateAdminPassword($admin['password_hash'], $newHash)) return [['_form' => 'Пароль уже изменён в другой вкладке. Войдите снова.'], 409];
    clearLoginFailures($key);
    session_regenerate_id(true);
    $_SESSION['password_version'] = hash('sha256', $newHash);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return [[], 200];
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
    $sidebar = isset($_SESSION['admin']) ? adminNavigation() : [];
    $sectionPanels = $view === 'backups' || ($view === 'editor' && ($data['key'] ?? '') === 'seo');
    $tabs = [];
    foreach ($sidebar as $group) if ($group['active'] && count($group['tabs']) > 1) $tabs = $group['tabs'];
    render('admin/layout', array_merge($data, ['title' => $title, 'view' => $view, 'csrf' => $_SESSION['csrf'], 'adminBrand' => getSettings('contacts')['name'], 'sidebar' => $sidebar, 'tabs' => $tabs, 'sectionPanels' => $sectionPanels]));
}
