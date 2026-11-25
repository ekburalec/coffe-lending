<div class="occasions__choices" data-occasion-tabs hidden aria-label="Выберите повод зайти">
<?php foreach ($occasionPanels as $index => $panel): ?><button class="occasions__choice" type="button" id="occasion-tab-<?= e($panel['id']) ?>" data-occasion-tab="<?= e($panel['id']) ?>" aria-controls="occasion-panel-<?= e($panel['id']) ?>"><span class="occasions__number" aria-hidden="true">0<?= $index + 1 ?></span><span><?= e($copy[$panel['title_key']]) ?></span><span class="occasions__arrow" aria-hidden="true">↗</span></button><?php endforeach; ?>
</div>
<div class="occasions__panels">
<?php foreach ($occasionPanels as $index => $panel): ?>
<article class="occasions__panel" id="occasion-panel-<?= e($panel['id']) ?>" data-occasion-panel="<?= e($panel['id']) ?>" aria-labelledby="occasion-title-<?= e($panel['id']) ?>">
    <div class="occasions__body"><span class="occasions__number" aria-hidden="true">0<?= $index + 1 ?> / 03</span><h3 class="occasions__title" id="occasion-title-<?= e($panel['id']) ?>" data-content-key="<?= e($panel['title_key']) ?>"><?= e($copy[$panel['title_key']]) ?></h3><p class="occasions__copy" data-content-key="<?= e($panel['body_key']) ?>"><?= e($copy[$panel['body_key']]) ?></p>
    <?php if ($panel['product']): ?><p class="occasions__suggestion"><strong><?= e($panel['product']['name']) ?></strong><span><?= e($panel['product']['portion']) ?> · <?= e(rubles((int) $panel['product']['price'])) ?></span></p><?php endif; ?>
    <a class="btn btn--secondary" href="<?= e($panel['target']) ?>" data-content-key="<?= e($panel['link_key']) ?>"><?= e($copy[$panel['link_key']]) ?><span aria-hidden="true">↗</span></a></div>
    <figure class="occasions__visual"><img class="occasions__image" src="<?= e(assetUrl($panel['image']['image'])) ?>" srcset="<?= e(assetUrl($panel['image']['image'])) ?> 1x, <?= e(assetUrl(str_replace('.webp', '@2x.webp', $panel['image']['image']))) ?> 2x" width="480" height="360" alt="<?= e($panel['image']['alt']) ?>" loading="lazy"></figure>
</article>
<?php endforeach; ?>
</div>
