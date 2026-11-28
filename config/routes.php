<?php
declare(strict_types=1);

return [
    'GET /admin/password' => 'passwordController',
    'POST /admin/password' => 'passwordController',
    'POST /admin/records/reorder' => 'reorderRecordsController',
    'POST /admin/products/reorder' => 'reorderProductsController',
    'GET /manifest.webmanifest' => 'manifestController',
    'GET /robots.txt' => 'discoveryController',
    'GET /sitemap.xml' => 'discoveryController',
    'GET /llms.txt' => 'discoveryController',
    'GET /llms-full.txt' => 'discoveryController',
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
