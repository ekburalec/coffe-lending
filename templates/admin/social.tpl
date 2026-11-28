<p class="admin__lead">Название используется в подсказке и для скринридеров. Пустой URL оставляет ссылку-заглушку.</p>
<form class="admin__form" method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?= e($csrf) ?>">
<?php foreach ($socialGroups as $group): ?>
<fieldset class="admin__social-group"><legend class="admin__category-title"><?= e($group['name']) ?></legend><div class="admin__social-fields"><?php render('admin/fields', ['fields' => $group['fields']]); ?></div></fieldset>
<?php endforeach; ?>
<button class="admin__button" type="submit">Сохранить соцсети</button>
</form>
