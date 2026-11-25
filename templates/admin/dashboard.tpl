<p class="admin__lead">Изменения в разделах появляются на сайте сразу после сохранения.</p>
<ul class="admin__overview">
<?php foreach ($sections as $section): ?><li><a class="admin__card" href="<?= e($section['href']) ?>"><span class="admin__card-title"><?= e($section['title']) ?></span><span class="admin__card-description"><?= e($section['description']) ?></span><span class="admin__card-arrow" aria-hidden="true">↗</span></a></li><?php endforeach; ?>
</ul>
