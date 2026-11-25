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
            <li class="reviews__item"><figure class="reviews__card"><blockquote class="reviews__quote"><p><?= e($review['text']) ?></p></blockquote><figcaption class="reviews__author"><strong><?= e($review['name']) ?></strong><a class="text-link reviews__source" href="<?= e($review['source']) ?>" target="_blank" rel="noopener noreferrer">Читать в источнике<span class="sr-only"> (откроется в новой вкладке)</span></a></figcaption></figure></li>
        <?php endforeach; ?>
        </ul>
        <p class="sr-only" data-reviews-status data-message="Отзывы {от}–{до} из {всего}" aria-live="polite" aria-atomic="true"></p>
        <?php else: ?><p class="reviews__empty">Отзывов пока нет.</p><?php endif; ?>
    </div>
</section>
