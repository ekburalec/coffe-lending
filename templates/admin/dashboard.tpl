<p>Выберите раздел. Сохранённые изменения сразу публикуются на сайте.</p>
<nav class="admin__grid" aria-label="Разделы управления">
<?php foreach ($sections as $key => $section): ?><a class="admin__card" href="/admin/edit?section=<?= e($key) ?>"><?= e($section['title']) ?></a><?php endforeach; ?>
</nav>
