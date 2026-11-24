<?php
declare(strict_types=1);

return [
    'GET /admin' => 'dashboardController',
    'GET /admin/login' => 'loginController',
    'POST /admin/login' => 'loginController',
    'POST /admin/logout' => 'logoutController',
    'GET /' => function (): void { readfile(APP_ROOT . '/index.html'); },
    'GET /index.php' => function (): void { redirect('/'); },
];
