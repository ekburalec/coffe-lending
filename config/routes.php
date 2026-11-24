<?php
declare(strict_types=1);

return [
    'GET /' => function (): void { readfile(APP_ROOT . '/index.html'); },
    'GET /index.php' => function (): void { redirect('/'); },
];
