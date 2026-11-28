<?php foreach ($products as $product): ?>
<?php if (!empty($product['available'])): ?>
<li class="order-preview__row" data-order-row="<?= e($product['id']) ?>" hidden>
    <img class="order-preview__image" src="<?= e(assetUrl($product['image'])) ?>" srcset="<?= e(assetUrl($product['image'])) ?> 1x, <?= e(assetUrl(str_replace('.webp', '@2x.webp', $product['image']))) ?> 2x" width="80" height="80" alt="<?= e($product['alt']) ?>" loading="lazy">
    <div class="order-preview__details"><h3 class="order-preview__name"><?= e($product['name']) ?></h3><p class="order-preview__portion"><?= e($product['portion']) ?></p><p class="order-preview__unit">Цена за штуку: <?= e(rubles((int) $product['price'])) ?></p><span class="order-preview__line-total" data-order-line-total>0 ₽</span></div>
    <div class="order-preview__actions"><div class="order-preview__quantity">
        <button class="order-preview__step" type="button" data-order-decrease="<?= e($product['id']) ?>" aria-label="Уменьшить количество: <?= e($product['name']) ?>">−</button>
        <span class="order-preview__count" data-order-quantity>0</span>
        <button class="order-preview__step" type="button" data-order-increase="<?= e($product['id']) ?>" aria-label="Увеличить количество: <?= e($product['name']) ?>">+</button>
        </div><button class="order-preview__remove" type="button" data-order-remove="<?= e($product['id']) ?>" aria-label="Удалить из заказа: <?= e($product['name']) ?>">Удалить</button>
    </div>
</li>
<?php endif; ?>
<?php endforeach; ?>
