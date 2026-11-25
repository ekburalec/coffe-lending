<div class="admin__actions"><?php if (!$singleton): ?><a href="/admin/edit?section=<?= e($key) ?>">Список</a><?php if (!($section['fixed'] ?? false)): ?><a class="admin__button" href="/admin/edit?section=<?= e($key) ?>&amp;new=1">Добавить</a><?php endif; ?><?php endif; ?></div>
<?php if ($notice): ?><p class="admin__success" role="status"><?= e($notice) ?></p><?php endif; ?>
<?php if ($errors): ?><div class="admin__message" role="alert" id="form-errors"><p>Изменения не сохранены:</p><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($editing): ?>
<?php if ($id): ?><h2><?= e($recordCaption) ?></h2><?php endif; ?>
<form class="admin__form admin__form--fields" method="post" enctype="multipart/form-data" autocomplete="off">
<input type="hidden" name="csrf" value="<?= e($csrf) ?>">
<?php foreach ($fields as $field): ?>
<div class="admin__field <?= in_array($field['type'], ['textarea', 'image', 'url'], true) ? 'admin__field--wide' : '' ?>">
<label for="field-<?= e($field['name']) ?>"><?= e($field['label']) ?></label>
<?php if ($field['type'] === 'textarea'): ?>
<textarea class="admin__input" id="field-<?= e($field['name']) ?>" name="<?= e($field['name']) ?>" rows="5" maxlength="<?= e($field['max'] ?? 5000) ?>" <?= !empty($field['required']) ? 'required' : '' ?> aria-invalid="<?= $field['error'] ? 'true' : 'false' ?>" aria-describedby="error-<?= e($field['name']) ?>"><?= e($field['value']) ?></textarea>
<?php elseif ($field['type'] === 'select'): ?>
<select class="admin__input" id="field-<?= e($field['name']) ?>" name="<?= e($field['name']) ?>" aria-describedby="error-<?= e($field['name']) ?>" aria-invalid="<?= $field['error'] ? 'true' : 'false' ?>"><?php foreach ($field['options'] ?? [] as $value => $label): ?><option value="<?= e($value) ?>" <?= (string) $field['value'] === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
<?php elseif ($field['type'] === 'image'): ?>
<input class="admin__input" id="field-<?= e($field['name']) ?>" name="<?= e($field['name']) ?>" value="<?= e($field['value']) ?>" readonly aria-describedby="error-<?= e($field['name']) ?>">
<label for="upload-<?= e($field['name']) ?>">Загрузить новую фотографию (JPG, PNG, WebP, до 10 МБ)</label><input class="admin__input" id="upload-<?= e($field['name']) ?>" type="file" name="<?= e($field['name']) ?>_upload" accept="image/jpeg,image/png,image/webp" aria-describedby="error-<?= e($field['name']) ?>">
<?php elseif ($field['type'] === 'checkbox'): ?>
<input id="field-<?= e($field['name']) ?>" type="checkbox" name="<?= e($field['name']) ?>" value="1" <?= $field['value'] ? 'checked' : '' ?> aria-describedby="error-<?= e($field['name']) ?>">
<?php else: ?>
<input class="admin__input" id="field-<?= e($field['name']) ?>" name="<?= e($field['name']) ?>" type="<?= e($field['type']) ?>" value="<?= e($field['value']) ?>" <?= !empty($field['required']) ? 'required' : '' ?> <?= $field['type'] === 'number' ? 'min="' . e($field['min'] ?? 0) . '" max="' . e($field['max'] ?? 1000000) . '" step="1"' : (in_array($field['type'], ['text', 'url', 'tel'], true) ? 'maxlength="' . e($field['max'] ?? 5000) . '"' : '') ?> aria-describedby="error-<?= e($field['name']) ?>" aria-invalid="<?= $field['error'] ? 'true' : 'false' ?>">
<?php endif; ?>
<span class="admin__hint admin__message" id="error-<?= e($field['name']) ?>"><?= e($field['error']) ?></span>
</div>
<?php endforeach; ?>
<button class="admin__button admin__button--submit" type="submit" name="action" value="save">Сохранить</button>
</form>
<?php if ($id && !($section['fixed'] ?? false) && !$singleton): ?>
<form class="admin__form" method="post"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><label for="confirm-delete"><input id="confirm-delete" name="confirm_delete" type="checkbox" value="1" required> Подтверждаю удаление записи. Восстановить её можно из резервной копии.</label><button class="admin__button" name="action" value="delete" type="submit">Удалить запись</button></form>
<?php endif; ?>
<?php elseif ($key === 'products'): ?>
<?php require __DIR__ . '/product-list.tpl'; ?>
<?php else: ?>
<ul class="admin__list"><?php foreach ($rows as $row): ?><li class="admin__row"><div class="admin__row-info"><a class="admin__row-name" href="/admin/edit?section=<?= e($key) ?>&amp;id=<?= e($row['id']) ?>"><?= e($row['caption']) ?></a><?php if (isset($row['price'])): ?><span class="admin__row-detail"><?= e($row['portion']) ?> · <?= e(rubles((int) $row['price'])) ?></span><?php endif; ?><span class="admin__badge"><?= !$row['visible'] ? 'Скрыто' : (isset($row['available']) && !$row['available'] ? 'Нет в наличии' : '') ?></span></div><a class="admin__button admin__button--quiet" href="/admin/edit?section=<?= e($key) ?>&amp;id=<?= e($row['id']) ?>">Изменить<span class="sr-only">: <?= e($row['caption']) ?></span></a></li><?php endforeach; ?></ul>
<?php if (!$rows): ?><p>Пока нет записей.</p><?php endif; ?>
<?php endif; ?>
