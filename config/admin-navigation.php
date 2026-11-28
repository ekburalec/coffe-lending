<?php
declare(strict_types=1);

return [
    ['key' => 'orders', 'title' => 'Заказы', 'href' => '/admin/orders', 'sections' => [], 'description' => 'Список, карточки и доска заказов.'],
    ['key' => 'order-settings', 'title' => 'Приём заказов', 'sections' => ['orders' => 'Настройки'], 'description' => 'Пауза, ограничения, реквизиты.'],
    ['key' => 'smtp', 'title' => 'Почта заказов', 'href' => '/admin/smtp', 'sections' => [], 'description' => 'SMTP и тестовое письмо.'],
    ['key' => 'home', 'title' => 'Главная', 'href' => '/admin', 'sections' => [], 'description' => 'Все разделы управления сайтом.'],
    ['key' => 'contacts', 'title' => 'Контакты и часы', 'sections' => ['contacts' => 'Контакты', 'hours' => 'Часы работы'], 'description' => 'Название, адрес, телефон, карта и расписание по дням недели.'],
    ['key' => 'social', 'title' => 'Соцсети', 'sections' => ['social' => 'Социальные сети'], 'description' => 'Полные названия, двухбуквенные подписи и URL пяти социальных ссылок.'],
    ['key' => 'schedule', 'title' => 'Особый график', 'sections' => ['exceptions' => 'Особые дни', 'announcements' => 'Объявления'], 'description' => 'Праздничные часы, выходные и объявления на заданные даты.'],
    ['key' => 'menu', 'title' => 'Меню кофейни', 'sections' => ['products' => 'Товары', 'categories' => 'Категории'], 'description' => 'Напитки и десерты: фотографии, цены, состав, наличие и порядок.'],
    ['key' => 'delivery', 'title' => 'Самовывоз и доставка', 'sections' => ['delivery' => 'Условия получения'], 'description' => 'Способы получения, стоимость доставки, зона и ориентиры времени.'],
    ['key' => 'content', 'title' => 'Содержимое сайта', 'sections' => ['content' => 'Тексты', 'images' => 'Фотографии', 'navigation' => 'Навигация'], 'description' => 'Заголовки и тексты блоков, фотографии и пункты меню сайта.'],
    ['key' => 'reviews', 'title' => 'Отзывы и рейтинг', 'sections' => ['reviews' => 'Отзывы', 'rating' => 'Рейтинг'], 'description' => 'Реальные отзывы гостей и рейтинг со ссылкой на источник.'],
    ['key' => 'faq', 'title' => 'Вопросы', 'sections' => ['faq' => 'Вопросы и ответы'], 'description' => 'Частые вопросы: ответы, порядок и видимость на сайте.'],
    ['key' => 'seo', 'title' => 'SEO', 'sections' => ['seo' => 'SEO и превью ссылки'], 'description' => 'Заголовок и описание для поиска, превью ссылки в мессенджерах.'],
    ['key' => 'backups', 'title' => 'Резервные копии', 'href' => '/admin/backups', 'sections' => [], 'description' => 'Скачать БД или архив с фотографиями и восстановить сохранённую версию.'],
    ['key' => 'password', 'title' => 'Пароль', 'href' => '/admin/password', 'sections' => [], 'utility' => true, 'description' => 'Сменить пароль для входа в панель управления.'],
    ['key' => 'visual-editor', 'title' => 'Визуальный редактор', 'href' => '/', 'sections' => [], 'utility' => true, 'description' => 'Открыть сайт с верхней панелью редактирования текстов и фотографий.'],
];
