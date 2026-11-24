<?php
declare(strict_types=1);

function dispatchRequest(): void
{
    $path = requestPath();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $routes = require APP_ROOT . '/config/routes.php';
    $handler = $routes[$method . ' ' . $path] ?? null;
    if ($handler) {
        $handler();
        return;
    }
    http_response_code(404);
    render('error', ['title' => 'Страница не найдена', 'message' => 'Проверьте адрес страницы.']);
}
