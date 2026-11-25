<?php if ($key === 'reviews'): ?>
<section class="admin__subsection" id="rating" aria-labelledby="rating-title"><h2 class="admin__category-title" id="rating-title">Рейтинг и источник</h2><p class="admin__lead">Укажите оценку, количество оценок и ссылку на реальный источник. Пустое значение рейтинга скрывает его на сайте.</p>
<form class="admin__form admin__form--fields" method="post" action="/admin/edit?section=rating" autocomplete="off"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="return_to" value="reviews">
<?php render('admin/fields', ['fields' => $ratingFields]); ?>
<button class="admin__button admin__button--submit" type="submit">Сохранить рейтинг</button></form></section>
<?php endif; ?>
<section class="admin__subsection" id="records" aria-labelledby="records-title"><h2 class="admin__category-title" id="records-title"><?= $key === 'reviews' ? 'Отзывы' : 'Частые вопросы' ?></h2>
<p class="admin__lead"><?= $key === 'reviews' ? 'Карточки на сайте идут в этом порядке, по три на широком экране. Если все отзывы скрыты, блок и ссылки на него не показываются.' : 'Вопросы на сайте идут в этом порядке. Скрытый вопрос остаётся в админке; на сайте и в разметке для поиска его нет.' ?></p>
<ul class="admin__list">
<?php foreach ($rows as $row): ?>
    <li class="admin__row admin__row--collection" id="record-<?= e($row['id']) ?>">
        <div class="admin__record-copy <?= !$row['visible'] ? 'admin__record-copy--hidden' : '' ?>"><h3 class="admin__row-name"><?= e($row['caption']) ?> <?php if (!$row['visible']): ?><span class="admin__badge">Скрыто</span><?php endif; ?></h3><p class="admin__row-detail"><?= e($row['preview']) ?></p><span class="admin__hint">Порядок: <?= e($row['sort_order']) ?></span></div>
        <div class="admin__actions">
            <a class="admin__button admin__button--quiet" href="/admin/edit?section=<?= e($key) ?>&amp;id=<?= e($row['id']) ?>">Изменить<span class="sr-only">: <?= e($row['caption']) ?></span></a>
            <form method="post" action="/admin/edit?section=<?= e($key) ?>&amp;id=<?= e($row['id']) ?>"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><button class="admin__button admin__button--quiet" type="submit" name="action" value="toggle_visibility"><?= $row['visible'] ? 'Скрыть' : 'Показать' ?><span class="sr-only">: <?= e($row['caption']) ?></span></button></form>
            <details class="admin__delete"><summary class="admin__button admin__button--quiet admin__button--danger">Удалить<span class="sr-only">: <?= e($row['caption']) ?></span></summary><form class="admin__delete-form" method="post" action="/admin/edit?section=<?= e($key) ?>&amp;id=<?= e($row['id']) ?>"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><label for="delete-<?= e($row['id']) ?>"><input id="delete-<?= e($row['id']) ?>" type="checkbox" name="confirm_delete" value="1" required> Подтверждаю удаление записи</label><button class="admin__button" type="submit" name="action" value="delete">Удалить запись</button></form></details>
        </div>
    </li>
<?php endforeach; ?>
</ul>
<?php if (!$rows): ?><p class="admin__lead"><?= $key === 'reviews' ? 'Пока нет отзывов. Добавьте реальный отзыв со ссылкой на источник.' : 'Пока нет вопросов.' ?></p><?php endif; ?>
</section>
<section class="admin__subsection" id="add-record" aria-labelledby="add-record-title"><h2 class="admin__category-title" id="add-record-title"><?= $key === 'reviews' ? 'Добавить отзыв' : 'Добавить вопрос' ?></h2>
<form class="admin__form admin__form--fields" method="post" action="/admin/edit?section=<?= e($key) ?>" autocomplete="off"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="return_to" value="list">
<?php render('admin/fields', ['fields' => $fields]); ?>
<button class="admin__button admin__button--submit" type="submit"><?= $key === 'reviews' ? 'Добавить отзыв' : 'Добавить вопрос' ?></button></form></section>
