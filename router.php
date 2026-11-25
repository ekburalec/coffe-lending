<?php
declare(strict_types=1);

// Только для php -S. Публичные файлы отдаются по явному списку.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$file = realpath(__DIR__ . $path);
$publicAsset = !in_array('..', explode('/', $path), true) && preg_match('~^/assets/(?:css|js|fonts|icons|img)/[a-zA-Z0-9_@./-]+\.(?:css|js|woff2?|svg|png|jpe?g|webp|ico)$~D', $path);
if ($file && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
    && is_file($file) && $publicAsset) {
    return false;
}
require __DIR__ . '/index.php';
