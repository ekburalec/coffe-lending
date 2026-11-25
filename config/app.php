<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$dataDirectory = getenv('APP_DATA_DIR') ?: (is_file(__DIR__ . '/release.php') ? dirname($root) . '/coffee-data' : $root . '/var');
return [
    'data_dir' => $dataDirectory,
    'upload_dir' => getenv('APP_UPLOAD_DIR') ?: $dataDirectory . '/media',
    'timezone' => 'Asia/Yekaterinburg',
    'session_timeout' => 1800,
];
