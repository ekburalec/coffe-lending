<p class="admin__lead" id="reorder-help">Перетащите товар за значок слева — порядок сохранится автоматически. С клавиатуры: Enter, затем стрелки, Enter для сохранения. Escape отменяет перемещение. Товары перемещаются внутри своей категории.</p>
<noscript><p class="admin__hint">Для перетаскивания включите JavaScript. Товары и формы редактирования доступны без него.</p></noscript>
<?php foreach ($productGroups as $group): ?>
<section class="admin__category" id="category-<?= e($group['id']) ?>" aria-labelledby="category-title-<?= e($group['id']) ?>">
    <h2 class="admin__category-title" id="category-title-<?= e($group['id']) ?>"><?= e($group['name']) ?><?php if (!$group['visible']): ?> <span class="admin__badge">Категория скрыта</span><?php endif; ?></h2>
    <p class="admin__hint" data-reorder-status aria-live="polite" aria-atomic="true"></p>
    <ul class="admin__list" data-reorder-list data-reorder-category="<?= e($group['id']) ?>" data-reorder-version="<?= e($group['version']) ?>" data-reorder-csrf="<?= e($csrf) ?>" data-reorder-url="/admin/products/reorder" data-message-moving="{название}: позиция {позиция} из {всего}." data-message-saving="Сохраняем порядок…" data-message-saved="Порядок товаров сохранён." data-message-cancelled="Перемещение отменено." data-message-error="Порядок не сохранён. Проверьте соединение и обновите страницу.">
    <?php foreach ($group['rows'] as $row): ?>
        <li class="admin__row admin__row--product" data-reorder-id="<?= e($row['id']) ?>" data-reorder-name="<?= e($row['name']) ?>">
            <button class="admin__drag-handle" type="button" data-reorder-handle aria-label="Переместить: <?= e($row['name']) ?>" aria-describedby="reorder-help" aria-pressed="false" hidden><svg width="20" height="24" viewBox="0 0 20 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="7" cy="6" r="1.5"/><circle cx="13" cy="6" r="1.5"/><circle cx="7" cy="12" r="1.5"/><circle cx="13" cy="12" r="1.5"/><circle cx="7" cy="18" r="1.5"/><circle cx="13" cy="18" r="1.5"/></svg></button>
            <div class="admin__row-info"><a class="admin__row-name" href="/admin/edit?section=products&amp;id=<?= e($row['id']) ?>"><?= e($row['name']) ?></a><span class="admin__row-detail"><?= e($row['portion']) ?> · <?= e(rubles((int) $row['price'])) ?></span><span class="admin__badge"><?= !$row['visible'] ? 'Скрыто' : (!$row['available'] ? 'Нет в наличии' : '') ?></span></div>
            <a class="admin__button admin__button--quiet" href="/admin/edit?section=products&amp;id=<?= e($row['id']) ?>">Изменить<span class="sr-only">: <?= e($row['name']) ?></span></a>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php if (!$group['rows']): ?><p class="admin__hint">В этой категории пока нет товаров.</p><?php endif; ?>
</section>
<?php endforeach; ?>
<?php if (!$productGroups): ?><p>Сначала добавьте категорию меню.</p><?php endif; ?>
