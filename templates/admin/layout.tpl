<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Управление данными кофейни"><meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — <?= e($adminBrand) ?></title>
    <link rel="stylesheet" href="<?= e(assetUrl('assets/css/main.css')) ?>">
<link rel="icon" href="/assets/icons/favicon.svg" type="image/svg+xml"></head>
<body class="admin">
<a class="skip-link" href="#admin-main">К содержимому</a>
<header class="admin__header"><a class="brand" href="/admin"><?= e($adminBrand) ?> <span class="admin__caption">/ управление</span></a><a href="/">Открыть сайт</a>
<?php if (isset($_SESSION['admin'])): ?><form method="post" action="/admin/logout"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><button class="admin__button" type="submit">Выйти</button></form><?php endif; ?>
</header>
<main class="admin__main" id="admin-main" tabindex="-1"><h1 class="admin__title"><?= e($title) ?></h1>
<?php require __DIR__ . '/' . $view . '.tpl'; ?>
</main>
</body></html>
