<?php
declare(strict_types=1);

function appConfig(string $key): mixed
{
    global $appConfig;
    return $appConfig[$key] ?? null;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function postText(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim(str_replace(["\r\n", "\r"], "\n", $value)) : '';
}

function render(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/templates/' . $template . '.tpl';
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function requestPath(): string
{
    return rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
}

function assetUrl(string $path): string
{
    static $assets;
    $assets ??= is_file(APP_ROOT . '/config/assets.php') ? require APP_ROOT . '/config/assets.php' : [];
    return '/' . ($assets[$path] ?? $path);
}
