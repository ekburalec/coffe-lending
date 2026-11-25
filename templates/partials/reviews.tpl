<section class="section reviews" id="reviews" aria-labelledby="reviews-title" data-reviews>
    <div class="container">
        <div class="reviews__header">
            <div><h2 class="section__title" id="reviews-title">Отзывы гостей</h2>
            <?php if ($rating['value'] !== '' && $rating['source'] && (int) $rating['count'] > 0): ?><p class="reviews__rating">Рейтинг <strong><?= e($rating['value']) ?></strong> из 5 · оценок: <?= e($rating['count']) ?>. <a class="text-link" href="<?= e($rating['source']) ?>" target="_blank" rel="noopener noreferrer">Источник рейтинга<span class="sr-only"> (откроется в новой вкладке)</span></a></p><?php endif; ?></div>
            <div class="reviews__controls" data-reviews-controls hidden><button class="reviews__control" type="button" data-reviews-previous aria-label="Предыдущие отзывы" aria-controls="reviews-track"><span aria-hidden="true">←</span></button><button class="reviews__control" type="button" data-reviews-next aria-label="Следующие отзывы" aria-controls="reviews-track"><span aria-hidden="true">→</span></button></div>
        </div>
        <?php if ($reviews): ?>
        <ul class="reviews__track" id="reviews-track" data-reviews-track tabindex="0" aria-label="Отзывы гостей. Список с горизонтальной прокруткой">
        <?php foreach ($reviews as $review): ?>
            <li class="reviews__item"><figure class="reviews__card">
                <?php if (!empty($review['is_demo'])): ?><p class="reviews__demo">Демо-отзыв. Текст и автор вымышлены; изображения созданы для примера.</p><?php endif; ?>
                <blockquote class="reviews__quote"><p><?= e($review['text']) ?></p></blockquote>
                <figcaption class="reviews__author">
                    <?php if (!empty($review['image'])): ?><img class="reviews__avatar" src="<?= e(assetUrl($review['image'])) ?>" srcset="<?= e(assetUrl($review['image'])) ?> 1x, <?= e(assetUrl(str_replace('.webp', '@2x.webp', $review['image']))) ?> 2x" width="64" height="64" alt="<?= e($review['alt'] ?? $review['name']) ?>" loading="lazy" decoding="async"><?php endif; ?>
                    <div class="reviews__identity"><strong><?= e($review['name']) ?></strong><?php if (empty($review['is_demo']) && !empty($review['source'])): ?><a class="text-link reviews__source" href="<?= e($review['source']) ?>" target="_blank" rel="noopener noreferrer">Читать в источнике<span class="sr-only"> (откроется в новой вкладке)</span></a><?php endif; ?></div>
                </figcaption>
            </figure></li>
        <?php endforeach; ?>
        </ul>
        <p class="sr-only" data-reviews-status data-message="Отзывы {от}–{до} из {всего}" aria-live="polite" aria-atomic="true"></p>
        <?php else: ?><p class="reviews__empty">Отзывов пока нет.</p><?php endif; ?>
    </div>
</section>
