<?php
declare(strict_types=1);

function loginController(): void
{
    startAdminSession();
    if (isset($_SESSION['admin'])) redirect('/admin');
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $key = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!validCsrf()) {
            http_response_code(403);
            $error = 'Форма устарела. Обновите страницу и повторите вход.';
        } elseif (loginBlocked($key)) {
            http_response_code(429);
            $error = 'Слишком много попыток. Повторите через 15 минут.';
        } else {
            $admin = adminAccount();
            $password = $_POST['password'] ?? '';
            $valid = is_string($password) && strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $admin['password_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            if ($valid && hash_equals($admin['username'], postText('username'))) {
                clearLoginFailures($key);
                session_regenerate_id(true);
                $_SESSION['admin'] = $admin['id'];
                $_SESSION['password_version'] = hash('sha256', $admin['password_hash']);
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                redirect('/admin');
            }
            recordLoginFailure($key);
            $error = 'Не удалось войти. Проверьте логин и пароль.';
            http_response_code(422);
        }
    }
    adminPage('Вход в админку', 'login', ['error' => $error]);
}

function logoutController(): void
{
    requireAdmin();
    if (!validCsrf()) {
        http_response_code(403);
        render('error', ['title' => 'Форма устарела', 'message' => 'Вернитесь назад и обновите страницу.']);
        return;
    }
    $_SESSION = [];
    session_regenerate_id(true);
    redirect('/admin/login');
}

function dashboardController(): void
{
    requireAdmin();
    $sections = array_values(array_filter(adminNavigation(), fn (array $group): bool => $group['key'] !== 'home'));
    adminPage('Управление кофейней', 'dashboard', ['sections' => $sections]);
}

function passwordController(): void
{
    requireAdmin();
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrf()) {
            $errors['_form'] = 'Форма устарела. Обновите страницу и повторите смену пароля.';
            http_response_code(403);
        } else {
            [$errors, $status] = changeAdminPassword($_POST);
            if (!$errors) redirect('/admin/password?changed=1');
            http_response_code($status);
        }
    }
    adminPage('Пароль администратора', 'password', ['errors' => $errors, 'changed' => isset($_GET['changed'])]);
}
