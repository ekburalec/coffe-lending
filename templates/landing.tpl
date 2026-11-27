<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require __DIR__ . '/partials/seo.tpl'; ?>

        <link rel="icon" href="assets/icons/favicon.svg?v=f28072225d" type="image/svg+xml" sizes="any">
        <link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png?v=7bb3af1282" sizes="180x180">
        <link rel="manifest" href="manifest.webmanifest?v=9e01f8e11c">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="<?= e($contacts['name']) ?>">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="preload" href="assets/fonts/cormorant-garamond-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="assets/fonts/manrope-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="stylesheet" href="<?= e(assetUrl('assets/css/main.css')) ?>">
        <script type="module" src="<?= e(assetUrl('assets/js/main.js')) ?>"></script>
    </head>
    <body id="top">
        <a class="skip-link" href="#main">Перейти к содержимому</a>
        <header class="site-header dark-mode" data-site-header>
            <?php require __DIR__ . '/partials/announcement.tpl'; ?>
            <div class="site-header__panel">
                <div class="container">
                    <div class="site-header__inner">
                        <a class="brand" href="#top">
                            <span class="brand__name"><?= e($contacts['name']) ?></span>
                        </a>
                        <nav class="site-header__nav" id="site-navigation" aria-label="Основная навигация" data-navigation>
                            <ul class="site-header__list"><?php foreach ($navigation as $link): ?><li><a class="site-header__link" href="<?= e($link['target']) ?>"><?= e($link['name']) ?></a></li><?php endforeach; ?></ul>
                        </nav>
                        <button class="site-header__order" type="button" data-order-open aria-haspopup="dialog" aria-controls="order" hidden>
<svg width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M5 7h14l1 14H4L5 7Zm4 0V5a3 3 0 0 1 6 0v2"></path></svg>
                            <span class="site-header__order-label">Корзина</span><output class="site-header__count" data-order-counter aria-label="Позиций в заказе: 0">0</output>
                        </button>
                        <a class="btn btn--primary site-header__cta" href="#menu"><?= e($copy['header-cta']) ?></a>
                        <button class="site-header__toggle" type="button" aria-label="Открыть меню" aria-expanded="false" aria-controls="site-navigation" data-navigation-toggle data-label-open="Открыть меню" data-label-close="Закрыть меню" hidden>
                            <svg width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">
                                <path d="M4 7h16M4 12h16M4 17h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        <div class="site-header__spacer dark-mode" data-header-spacer aria-hidden="true" hidden></div>
        <main id="main" tabindex="-1">
            <section class="hero dark-mode" id="welcome" aria-labelledby="hero-title">
                <div class="container hero__inner">
                    <div class="hero__copy">
                        <p class="section__eyebrow" data-content-key="copy-0"><?= e($contacts['name'] . ' · кофейня · ' . $contacts['city']) ?></p>
                        <h1 class="hero__title" id="hero-title" data-content-key="copy-1"><?= e($copy['copy-1']) ?></h1>
                        <p class="hero__lead" data-content-key="copy-2"><?= e($copy['copy-2']) ?></p>
                        <div class="hero__actions">
                            <a class="btn btn--primary" href="#menu" data-content-key="copy-3"><?= e($copy['copy-3']) ?></a><a class="text-link" href="#space" data-content-key="copy-4"><?= e($copy['copy-4']) ?></a>
                        </div>
                        <p class="hero__facts" data-content-key="copy-5"><?= e($hours['special'] ? $hours['today'] : $hours['weekly']) ?> · <?= e(implode(' и ', array_filter([!empty($delivery['pickup_enabled']) ? 'Самовывоз' : '', !empty($delivery['delivery_enabled']) ? 'Доставка' : '']))) ?></p>
                        <p class="hero__note" data-content-key="copy-6"><?= e($copy['copy-6']) ?></p>
                    </div>
                    <figure class="hero__visual media-frame">
                        <img class="media-frame__image" src="<?= e($images['image-0']['image']) ?>" srcset="<?= e($images['image-0']['image'] . ' 1x, ' . str_replace('.webp', '@2x.webp', $images['image-0']['image']) . ' 2x') ?>" width="560" height="700" alt="<?= e($images['image-0']['alt']) ?>" loading="eager" fetchpriority="high" data-image-key="image-0">
                    </figure>
                </div>
            </section>
            <section class="section menu" id="menu" aria-labelledby="menu-title">
                <div class="container">
                    <div class="menu__intro">
                        <div>
                            <p class="section__eyebrow" data-content-key="copy-7"><?= e($copy['copy-7']) ?></p>
                            <h2 class="section__title" id="menu-title" data-order-menu-focus tabindex="-1" data-content-key="copy-8"><?= e($copy['copy-8']) ?></h2>
                            <p class="section__lead" data-content-key="copy-9"><?= e($copy['copy-9']) ?></p>
                        </div>
                    </div>
                    <nav class="menu__categories" aria-label="Категории меню"><?php foreach ($categories as $category): ?><a class="text-link" href="#<?= e($category['id']) ?>"><?= e($category['name']) ?></a><?php endforeach; ?></nav>
                    <p class="menu__note" data-content-key="copy-13"><?= e($copy['copy-13']) ?></p>
                    <noscript><p class="menu__note">
                            Для выбора позиций включите JavaScript. Меню, составы и цены доступны
                            без него.
                        </p></noscript>
                    <?php require __DIR__ . '/partials/catalog.tpl'; ?>
<div class="menu__footer">
                        <p class="section__note" data-content-key="copy-17"><?= e($copy['copy-17']) ?></p>
                        <button class="text-link text-link--button" type="button" data-order-open aria-haspopup="dialog" aria-controls="order" hidden>
                            Посмотреть состав заказа
                        </button>
                    </div>
                </div>
            </section>
            <section class="section section--surface occasions" id="occasions" aria-labelledby="occasions-title" data-occasions>
                <div class="container">
                    <p class="section__eyebrow" data-content-key="copy-18"><?= e($copy['copy-18']) ?></p>
                    <h2 class="section__title" id="occasions-title" data-content-key="copy-19"><?= e($copy['copy-19']) ?></h2>
                    <p class="section__lead" data-content-key="copy-20"><?= e($copy['copy-20']) ?></p>
                    <?php require __DIR__ . '/partials/occasions.tpl'; ?>
                </div>
            </section>
            <section class="section space dark-mode" id="space" aria-labelledby="space-title">
                <div class="container space__inner">
                    <div class="space__copy">
                        <p class="section__eyebrow" data-content-key="copy-30"><?= e($copy['copy-30']) ?></p>
                        <h2 class="section__title" id="space-title" data-content-key="copy-31"><?= e($copy['copy-31']) ?></h2>
                        <p class="section__lead" data-content-key="copy-32"><?= e($copy['copy-32']) ?></p>
                        <p data-content-key="copy-33"><?= e($copy['copy-33']) ?></p>
                        <ul class="space__facts">
                            <li>Столики для встреч</li>
                            <li>Wi-Fi</li>
                            <li>Розетки у общего стола</li>
                        </ul>
                        <a class="text-link" href="#contacts" data-content-key="copy-34"><?= e($copy['copy-34']) ?></a>
                    </div>
                    <figure class="space__visual media-frame">
                        <img class="media-frame__image" src="<?= e($images['image-1']['image']) ?>" srcset="<?= e($images['image-1']['image'] . ' 1x, ' . str_replace('.webp', '@2x.webp', $images['image-1']['image']) . ' 2x') ?>" width="720" height="450" alt="<?= e($images['image-1']['alt']) ?>" loading="lazy" data-image-key="image-1">
                    </figure>
                </div>
            </section>
            <section class="section section--surface delivery" id="delivery" aria-labelledby="delivery-title">
                <div class="container">
                    <p class="section__eyebrow" data-content-key="copy-35"><?= e($copy['copy-35']) ?></p>
                    <h2 class="section__title" id="delivery-title" data-content-key="copy-36"><?= e($copy['copy-36']) ?></h2>
                    <p class="section__lead" data-content-key="copy-37"><?= e($copy['copy-37']) ?></p>
                    <?php require __DIR__ . '/partials/delivery.tpl'; ?>
<p class="section__note delivery__note" data-content-key="copy-38"><?= e($copy['copy-38']) ?></p>
                    <a class="text-link" href="#menu" data-content-key="copy-39"><?= e($copy['copy-39']) ?></a>
                </div>
            </section>
            <?php require __DIR__ . '/partials/reviews.tpl'; ?>
            <?php if ($faq): ?><section class="section faq" id="faq" aria-labelledby="faq-title">
                <div class="container faq__inner">
                    <div>
                        <p class="section__eyebrow"><?= e($copy['faq-eyebrow']) ?></p>
                        <h2 class="section__title" id="faq-title">
                            <?= e($copy['faq-title']) ?>
                        </h2>
                    </div>
                    <?php require __DIR__ . '/partials/faq.tpl'; ?>
</div>
            </section><?php endif; ?>

</main>
        <footer class="site-footer dark-mode" id="contacts">
            <div class="container">
                <div class="site-footer__inner">
                    <div class="site-footer__about">
                        <a class="brand" href="#top"><span class="brand__name"><?= e($contacts['name']) ?></span></a>
                        <p class="site-footer__caption"><?= e($copy['footer-caption']) ?></p>
                        <nav class="site-footer__social" aria-label="Социальные сети"><h2 class="site-footer__title">Мы в соцсетях</h2><ul class="site-footer__social-list">
                        <?php foreach ($socialLinks as $social): ?><li><a class="site-footer__social-link" title="<?= e($social['name']) ?>" href="<?= e($social['url']) ?>" <?php if ($social['placeholder']): ?>data-social-placeholder data-message="<?= e('Ссылка «' . $social['name'] . '» пока не добавлена.') ?>"<?php else: ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>><span aria-hidden="true"><?= e($social['label']) ?></span><span class="sr-only"><?= e($social['name']) ?><?= $social['placeholder'] ? ' (ссылка пока не добавлена)' : ' (откроется в новой вкладке)' ?></span></a></li><?php endforeach; ?>
                        </ul><?php if ($socialNotice): ?><p class="site-footer__social-note"><?= e($socialNotice) ?></p><?php endif; ?></nav>
                    </div>
                    <div class="site-footer__contacts">
                        <div><h2 class="site-footer__title">Загляните к нам</h2><address><?= e($contacts['city']) ?>,<br><?= e($contacts['street']) ?></address><?php if ($contacts['map_url']): ?><a class="text-link" href="<?= e($contacts['map_url']) ?>" target="_blank" rel="noopener noreferrer">На карте<span class="sr-only"> (откроется в новой вкладке)</span></a><?php endif; ?></div>
                        <div><h2 class="site-footer__title">Часы работы</h2><p><?= e($hours['weekly']) ?><br><?= e($hours['today']) ?></p></div>
                        <div><h2 class="site-footer__title">Телефон</h2><p><?= e($contacts['phone']) ?></p></div>
                    </div>
                </div>
                <div class="site-footer__bottom">
                    <p>&copy; <?= e($contacts['name']) ?></p>
                    <a class="text-link" href="#top">Наверх</a>
                </div>
            </div>
        </footer>
        <dialog class="order-preview" id="order" aria-labelledby="order-title" data-order-preview data-label-delivery="Доставка" data-label-pickup="Самовывоз" data-hint-pickup="Самовывоз без доплаты." data-hint-empty="Добавьте позиции, чтобы рассчитать доставку." data-hint-paid="<?= e($delivery['zone'] . ': ' . rubles((int) $delivery['fee']) . '.') ?>" data-hint-free="<?= e('Доставка бесплатная: сумма товаров от ' . rubles((int) $delivery['threshold']) . '.') ?>" data-counter-label="Позиций в заказе: {количество}" data-message-added="Добавлено: {название}" data-message-changed="Количество изменено: {название}, {количество}" data-message-removed="Удалено: {название}" data-delivery-fee="<?= e($delivery['fee']) ?>" data-free-delivery-from="<?= e($delivery['threshold']) ?>" data-order-drawer>
            <div class="order-preview__header">
                <div class="order-preview__heading">
                    <p class="order-preview__eyebrow"><?= e($contacts['name']) ?></p>
                    <h2 class="order-preview__title" id="order-title">Ваша корзина</h2>
                </div>
                <button class="order-preview__close" type="button" aria-label="Закрыть корзину" data-order-close autofocus>
                    <svg width="24" height="24" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">
                        <path d="m6 6 12 12M6 18 18 6"></path>
                    </svg>
                </button>
            </div>
            <div class="order-preview__content">
                <div class="order-preview__empty" data-order-empty>
                    <h3>Здесь появится ваш вкусный повод</h3>
                    <p>Добавьте напиток или десерт из меню, чтобы посмотреть состав и сумму.</p>
                    <a class="btn btn--primary" href="#menu" data-order-empty-link data-order-dismiss>Перейти к меню</a>
                </div>
                <ul class="order-preview__items" aria-label="Выбранные позиции" data-order-items><?php require __DIR__ . '/partials/cart-items.tpl'; ?></ul>
                <div class="order-preview__summary" data-order-summary hidden>
                    <div class="order-preview__summary-inner">
                        <fieldset class="order-preview__fieldset" data-order-controls hidden aria-describedby="delivery-hint">
                            <legend class="order-preview__legend">Способ получения</legend>
                            <div class="order-preview__choices">
<?php if (!empty($delivery['pickup_enabled'])): ?><div class="order-preview__choice"><input id="pickup" type="radio" name="receiving" value="pickup" checked data-receiving><label for="pickup">Самовывоз</label></div><?php endif; ?>
<?php if (!empty($delivery['delivery_enabled'])): ?><div class="order-preview__choice"><input id="courier" type="radio" name="receiving" value="delivery" <?= empty($delivery['pickup_enabled']) ? 'checked' : '' ?> data-receiving><label for="courier">Доставка</label></div><?php endif; ?>
</div><p class="order-preview__note" id="delivery-hint"><?= e(deliveryDescription($delivery)) ?></p>
                        </fieldset>
                        <dl class="order-preview__calculation">
                            <div class="order-preview__calculation-row">
                                <dt>Товары</dt>
                                <dd data-order-subtotal>0 ₽</dd>
                            </div>
                            <div class="order-preview__calculation-row">
                                <dt data-order-delivery-label>Самовывоз</dt>
                                <dd data-order-delivery>0 ₽</dd>
                            </div>
                            <div class="order-preview__calculation-row order-preview__calculation-row--total">
                                <dt>Итого</dt>
                                <dd>
                                    <output data-order-total aria-live="polite" aria-atomic="true">0 ₽</output>
                                </dd>
                            </div>
                        </dl>
                        <p class="order-preview__note" data-order-delivery-hint>
                            Самовывоз без доплаты.
                        </p>
                    </div>
                </div>
                <div class="order-preview__more" data-order-more hidden>
                    <a class="text-link" href="#menu" data-order-dismiss>Добавить ещё что-нибудь</a>
                </div>
                <p class="order-preview__notice">
                    Состав заказа не отправляется в кофейню. Оплата не требуется.
                </p>
            </div>
        </dialog>
    </body>
</html>
