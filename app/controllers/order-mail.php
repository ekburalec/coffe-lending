<?php
declare(strict_types=1);

function adminSmtpController(): void
{
    requireAdmin(); $errors = []; $notice = ''; $config = smtpSettings();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrf()) { http_response_code(403); $errors['_form'] = 'Форма устарела.'; }
        else try {
            if (postText('action') === 'test') { sendSmtpMail($config, $config['manager'], 'Проверка почты — Вкусный повод', 'SMTP работает.'); $notice = 'Тестовое письмо отправлено.'; }
            else {
                $presets = ['mailpit' => ['host' => '127.0.0.1', 'port' => 1025, 'security' => 'none'], 'yandex' => ['host' => 'smtp.yandex.ru', 'port' => 465, 'security' => 'tls'], 'gmail' => ['host' => 'smtp.gmail.com', 'port' => 465, 'security' => 'tls']];
                foreach (['host', 'port', 'security', 'username', 'from', 'manager'] as $key) $config[$key] = postText($key);
                if (isset($presets[postText('preset')])) $config = array_merge($config, $presets[postText('preset')]);
                if (postText('password') !== '') $config['password'] = postText('password');
                foreach (['username', 'password'] as $key) if (strlen($config[$key]) > 1000) throw new InvalidArgumentException('Параметры авторизации слишком длинные.');
                if (!filter_var($config['from'], FILTER_VALIDATE_EMAIL) || !filter_var($config['manager'], FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9.-]{1,253}$/D', $config['host']) || !in_array($config['security'], ['none', 'tls', 'starttls'], true) || !ctype_digit((string) $config['port']) || (int) $config['port'] < 1 || (int) $config['port'] > 65535) throw new InvalidArgumentException('Проверьте SMTP и адреса.');
                $path = appConfig('data_dir') . '/smtp.json'; $temp = $path . '.tmp';
                if (file_put_contents($temp, json_encode($config, JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Cannot save SMTP');
                chmod($temp, 0600); if (!rename($temp, $path)) throw new RuntimeException('Cannot save SMTP');
                $notice = 'Настройки сохранены.';
            }
        } catch (Throwable $error) { http_response_code(422); $errors['_form'] = $error instanceof InvalidArgumentException ? $error->getMessage() : 'Не удалось отправить или сохранить. Проверьте соединение и настройки.'; }
    }
    unset($config['password']);
    adminPage('Почта заказов', 'smtp', compact('config', 'errors', 'notice'));
}
