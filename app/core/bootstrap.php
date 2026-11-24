<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__, 2));
$appConfig = require APP_ROOT . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);
ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/database.php';
require_once APP_ROOT . '/app/models/content.php';

set_exception_handler(function (Throwable $error): void {
    error_log((string) $error);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Ошибка приложения. Проверьте журнал PHP.\n");
        exit(1);
    }
    http_response_code(500);
    render('error', ['title' => 'Временная ошибка', 'message' => 'Не удалось выполнить действие. Попробуйте позже.']);
});
