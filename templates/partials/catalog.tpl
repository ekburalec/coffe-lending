<?php foreach ($categories as $category): ?>
<div class="menu__category" id="<?= e($category['id']) ?>">
    <h3 class="menu__category-title"><?= e($category['name']) ?></h3>
    <ul class="menu__products">
    <?php foreach ($category['products'] as $index => $product): ?>
        <li <?= visualManagedAttributes('products', $visualPreview, $product['id']) ?> class="product-card <?= $index === 0 ? 'product-card--featured' : 'product-card--compact' ?>" data-product="<?= e($product['id']) ?>">
            <img class="product-card__image" src="<?= e(assetUrl($product['image'])) ?>" srcset="<?= e(assetUrl($product['image'])) ?> 1x, <?= e(assetUrl(str_replace('.webp', '@2x.webp', $product['image']))) ?> 2x" width="480" height="360" alt="<?= e($product['alt']) ?>" loading="lazy">
            <article class="product-card__body" aria-labelledby="product-<?= e($product['id']) ?>">
                <h4 class="product-card__title" id="product-<?= e($product['id']) ?>" data-product-name><?= e($product['name']) ?></h4>
                <p class="product-card__description"><?= e($product['description']) ?></p>
                <p class="product-card__portion"><?= e($product['portion']) ?></p>
                <details class="product-card__ingredients"><summary>Состав</summary><p><?= e($product['ingredients']) ?></p></details>
                <div class="product-card__bottom"><data class="product-card__price" value="<?= e($product['price']) ?>" data-unit-price><?= e(rubles((int) $product['price'])) ?></data>
                <?php if (!empty($product['available'])): ?><button class="btn btn--secondary" type="button" data-order-add="<?= e($product['id']) ?>" aria-label="Добавить в заказ: <?= e($product['name']) ?>" hidden>Добавить</button><?php else: ?><span class="product-card__portion">Нет в наличии</span><?php endif; ?>
                </div>
            </article>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php if (!$category['products']): ?><p class="menu__note">В этой категории пока нет позиций.</p><?php endif; ?>
</div>
<?php endforeach; ?>
<?php if (!$categories): ?><p class="menu__note">Меню обновляется. Загляните позже.</p><?php endif; ?>
