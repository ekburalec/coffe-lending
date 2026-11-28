<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Управление данными кофейни"><meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — <?= e($adminBrand) ?></title>
    <link rel="stylesheet" href="<?= e(assetUrl('assets/css/main.css')) ?>">
<script type="module" src="<?= e(assetUrl('assets/js/admin.js')) ?>"></script>
<link rel="icon" href="/assets/icons/favicon.svg" type="image/svg+xml"></head>
<body class="admin">
<a class="skip-link" href="#admin-main">К содержимому</a>
<header class="admin__header"><div class="admin__header-inner"><a class="admin__brand" href="/admin"><?= e($adminBrand) ?><span class="admin__caption">Панель управления</span></a><div class="admin__actions"><a class="admin__site-link" href="/" target="_blank" rel="noopener noreferrer">На сайт<span class="sr-only"> (откроется в новой вкладке)</span></a>
<?php if ($sidebar): ?><form method="post" action="/admin/logout"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><button class="admin__button admin__button--quiet" type="submit">Выйти</button></form><?php endif; ?>
</div></div>
</header>
<div class="admin__workspace <?= !$sidebar ? 'admin__workspace--login' : '' ?>">
<?php if ($sidebar): ?>
<aside class="admin__sidebar"><nav aria-label="Разделы управления"><ul class="admin__nav-list">
<?php foreach ($sidebar as $group): ?><li><a class="admin__nav-link" href="<?= e($group['href']) ?>" <?= $group['active'] ? 'aria-current="page"' : '' ?>><?= e($group['title']) ?></a></li><?php endforeach; ?>
</ul></nav></aside>
<?php endif; ?>
<main class="admin__main <?= $sectionPanels ? 'admin__main--sections' : '' ?>" id="admin-main" tabindex="-1"><?php if (!$sectionPanels): ?><h1 class="admin__title"><?= e($title) ?></h1><?php endif; ?>
<?php if ($tabs): ?><nav class="admin__tabs" aria-label="Подразделы"><?php foreach ($tabs as $tab): ?><a class="admin__tab" href="<?= e($tab['href']) ?>" <?= $tab['active'] ? 'aria-current="page"' : '' ?>><?= e($tab['title']) ?></a><?php endforeach; ?></nav><?php endif; ?>
<?php require __DIR__ . '/' . $view . '.tpl'; ?>
</main>
</div>
</body></html>
