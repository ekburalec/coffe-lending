<?php
declare(strict_types=1);

return [
    'GET /admin/backups' => 'backupsController',
    'POST /admin/backups' => 'backupsController',
    'GET /admin/edit' => 'adminEditorController',
    'POST /admin/edit' => 'adminEditorController',
    'GET /admin' => 'dashboardController',
    'GET /admin/login' => 'loginController',
    'POST /admin/login' => 'loginController',
    'POST /admin/logout' => 'logoutController',
    'GET /' => 'landingController',
    'GET /index.php' => function (): void { redirect('/'); },
];
