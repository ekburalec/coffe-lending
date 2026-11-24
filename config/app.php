<?php
declare(strict_types=1);

return [
    'data_dir' => getenv('APP_DATA_DIR') ?: dirname(__DIR__) . '/var',
    'upload_dir' => getenv('APP_UPLOAD_DIR') ?: dirname(__DIR__) . '/assets/img/uploads',
    'timezone' => 'Asia/Yekaterinburg',
    'session_timeout' => 1800,
];
