<?php
declare(strict_types=1);

// Только для php -S. Публичные файлы отдаются по явному списку.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (preg_match('~^/assets/img/uploads/([a-f0-9]{32}(?:@2x)?\.(?:webp|jpg))$~D', $path, $match)) {
    $config = require __DIR__ . '/config/app.php';
    $upload = $config['upload_dir'] . '/' . $match[1];
    if (is_file($upload)) {
        header('Content-Type: ' . (str_ends_with($upload, '.jpg') ? 'image/jpeg' : 'image/webp'));
        header('X-Content-Type-Options: nosniff');
        readfile($upload);
        return true;
    }
}
$file = realpath(__DIR__ . $path);
$publicAsset = preg_match('~^/assets/(?:css|js|fonts|icons|img)/[a-zA-Z0-9_@./-]+\.(?:css|js|woff2?|svg|png|jpe?g|webp|ico)$~D', $path);
if ($file && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
    && is_file($file) && ($publicAsset || $path === '/manifest.webmanifest')) {
    return false;
}
require __DIR__ . '/index.php';
