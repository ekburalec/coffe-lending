<?php if (!in_array($key, ['reviews', 'faq'], true) || $editing): ?><div class="admin__actions"><?php if (!$singleton): ?><a href="/admin/edit?section=<?= e($key) ?>">Список</a><?php if (!($section['fixed'] ?? false)): ?><a class="admin__button" href="/admin/edit?section=<?= e($key) ?>&amp;new=1">Добавить</a><?php endif; ?><?php endif; ?></div><?php endif; ?>
<?php if ($notice): ?><p class="admin__success" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($errors): ?><div class="admin__message" role="alert" id="form-errors"><p>Изменения не сохранены:</p><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($key === 'seo'): ?>
<?php require __DIR__ . '/seo.tpl'; ?>
<?php elseif ($key === 'social'): ?>
<?php require __DIR__ . '/social.tpl'; ?>
<?php elseif ($editing): ?>
<?php if ($id): ?><h2><?= e($recordCaption) ?></h2><?php endif; ?>
<form class="admin__form admin__form--fields" method="post" enctype="multipart/form-data" autocomplete="off">
<input type="hidden" name="csrf" value="<?= e($csrf) ?>">
<?php render('admin/fields', ['fields' => $fields]); ?>
<button class="admin__button admin__button--submit" type="submit" name="action" value="save">Сохранить</button>
</form>
<?php if ($id && !($section['fixed'] ?? false) && !$singleton): ?>
<form class="admin__form" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><label for="confirm-delete"><input id="confirm-delete" name="confirm_delete" type="checkbox" value="1" required> Подтверждаю удаление записи. Восстановить её можно из резервной копии.</label><button class="admin__button" name="action" value="delete" type="submit">Удалить запись</button></form>
<?php endif; ?>
<?php elseif (in_array($key, ['reviews', 'faq'], true)): ?>
<?php require __DIR__ . '/collection-list.tpl'; ?>
<?php elseif ($key === 'products'): ?>
<?php require __DIR__ . '/product-list.tpl'; ?>
<?php else: ?>
<?php if ($key === 'navigation'): render('admin/reorder-help'); endif; ?>
<ul class="admin__list"<?php if ($key === 'navigation'): render('admin/reorder-attributes', compact('key', 'listOrderVersion', 'csrf')); endif; ?>><?php foreach ($rows as $row): ?><li class="admin__row <?= $key === 'navigation' ? 'admin__row--sortable' : '' ?>" <?= $key === 'navigation' ? 'data-reorder-id="' . e($row['id']) . '" data-reorder-name="' . e($row['caption']) . '"' : '' ?>><?php if ($key === 'navigation'): render('admin/reorder-handle', compact('row')); endif; ?><div class="admin__row-info"><a class="admin__row-name" href="/admin/edit?section=<?= e($key) ?>&amp;id=<?= e($row['id']) ?>"><?= e($row['caption']) ?></a><?php if (isset($row['price'])): ?><span class="admin__row-detail"><?= e($row['portion']) ?> · <?= e(rubles((int) $row['price'])) ?></span><?php endif; ?><span class="admin__badge"><?= !$row['visible'] ? 'Скрыто' : (isset($row['available']) && !$row['available'] ? 'Нет в наличии' : '') ?></span></div><a class="admin__button admin__button--quiet admin__button--compact" href="/admin/edit?section=<?= e($key) ?>&amp;id=<?= e($row['id']) ?>">Изменить<span class="sr-only">: <?= e($row['caption']) ?></span></a></li><?php endforeach; ?></ul>
<?php if (!$rows): ?><p>Пока нет записей.</p><?php endif; ?>
<?php endif; ?>
