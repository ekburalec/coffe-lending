<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="description" content="Контакты и получение заказа в кофейне Вкусный повод"><title>Оформление заказа — Вкусный повод</title><link rel="icon" href="/assets/icons/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="<?= e(assetUrl('assets/css/main.css')) ?>"></head>
<body><main class="checkout" id="main"><a href="/">Вернуться в кофейню</a><h1>Оформление заказа</h1>
<?php if (empty($settings['enabled'])): ?><p class="checkout__notice"><?= e($settings['pause_message']) ?></p><?php endif; ?>
<?php if ($errors): ?><div class="checkout__error" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<section id="checkout-cart" aria-labelledby="cart-heading"><h2 id="cart-heading">Состав и получение</h2>
<?php if ($quote): ?><details><summary>Изменить состав, пожелания или получение</summary><?php endif; ?>
<form method="post" action="/checkout" class="checkout__form"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="cart">
<label for="receiving">Способ получения</label><select class="admin__input" id="receiving" name="receiving"><option value="pickup" <?= $receiving === 'pickup' ? 'selected' : '' ?>>Самовывоз</option><option value="delivery" <?= $receiving === 'delivery' ? 'selected' : '' ?>>Доставка по Екатеринбургу</option></select>
<?php foreach ($products as $product): ?><div class="checkout__item"><label for="qty-<?= e($product['id']) ?>"><?= e($product['name']) ?> · <?= e($product['portion']) ?> · <?= e(rubles($product['price'])) ?></label><input class="admin__input" id="qty-<?= e($product['id']) ?>" name="quantities[<?= e($product['id']) ?>]" type="number" min="0" max="99" value="<?= e($quantities[$product['id']] ?? 0) ?>"><label for="comment-<?= e($product['id']) ?>">Пожелание к позиции</label><input class="admin__input" id="comment-<?= e($product['id']) ?>" name="comments[<?= e($product['id']) ?>]" maxlength="500" value="<?= e($comments[$product['id']] ?? '') ?>"></div><?php endforeach; ?>
<button class="btn btn--primary" type="submit">Пересчитать состав</button></form><?php if ($quote): ?></details><?php endif; ?></section>
<?php if ($quote): ?><section id="checkout-details" aria-labelledby="details-heading"><h2 id="details-heading">Контакты и подтверждение</h2>
<ul><?php foreach ($quote['items'] as $item): ?><li><?= e($item['name']) ?> × <?= e($item['quantity']) ?>: <?= e(rubles($item['price'] * $item['quantity'])) ?></li><?php endforeach; ?></ul>
<p>Товары: <?= e(rubles($quote['subtotal'])) ?>. Доставка: <?= e(rubles($quote['fee'])) ?>.</p><p class="checkout__total">Итого: <?= e(rubles($quote['total'])) ?></p>
<?php if ($receiving === 'pickup'): ?><p>Самовывоз: <?= e($pickupAddress) ?>.</p><?php endif; ?>
<p>Состав, адрес в зоне доставки и время подтвердит менеджер. Онлайн-оплаты нет.</p>
<form method="post" action="/checkout" class="checkout__form"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><input type="hidden" name="action" value="place"><input type="hidden" name="request_key" value="<?= e($requestKey) ?>"><input type="hidden" name="fingerprint" value="<?= e($quote['fingerprint']) ?>">
<?php require __DIR__ . '/partials/order-fields.tpl'; ?>
<div class="checkout__trap" aria-hidden="true"><label for="website">Сайт</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
<label for="consent"><input id="consent" name="consent" type="checkbox" value="1" required aria-describedby="consent-error"> Согласен на обработку данных для выполнения заказа и ознакомлен с <a href="/privacy" target="_blank" rel="noopener noreferrer">политикой<span class="sr-only"> (откроется в новой вкладке)</span></a></label><p id="consent-error"><?= e($errors['consent'] ?? '') ?></p>
<button class="btn btn--primary" type="submit" <?= empty($settings['enabled']) ? 'disabled' : '' ?>>Оформить заказ</button></form></section><?php endif; ?>
</main></body></html>
