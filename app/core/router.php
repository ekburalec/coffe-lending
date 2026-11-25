<?php
declare(strict_types=1);

function dispatchRequest(): void
{
    $path = requestPath();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET' && preg_match('~^/assets/img/uploads/([a-f0-9]{32}(?:@2x)?\.(?:webp|jpg))$~D', $path, $match)) {
        uploadedMediaController($match[1]);
        return;
    }
    $routes = require APP_ROOT . '/config/routes.php';
    $handler = $routes[$method . ' ' . $path] ?? null;
    if ($handler) {
        $handler();
        return;
    }
    http_response_code(404);
    render('error', ['title' => 'Страница не найдена', 'message' => 'Проверьте адрес страницы.']);
}
