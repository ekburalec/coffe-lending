<?php
declare(strict_types=1);

function landingController(): void
{
    header('Cache-Control: no-cache');
    header('Vary: Cookie');
    startAdminSession(false);
    $data = landingData();
    $data['orderCsrf'] = $_SESSION['csrf'];
    $data['seo'] = landingSeo($data);
    if (isset($_COOKIE['coffee_admin'])) {
        startAdminSession();
        if (isset($_SESSION['admin'])) {
            $data['visualPreview'] = true;
            $data['visualState'] = visualEditorState();
            $data['csrf'] = $_SESSION['csrf'];
            header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self'; script-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
        }
    }
    render('landing', $data);
}
