<?php
declare(strict_types=1);

$socialFields = [];
foreach (socialNetworkDefaults() as $key => $network) {
    $socialFields[$key . '_name'] = ['label' => 'Полное название', 'required' => true, 'max' => 80, 'default' => $network['name']];
    $socialFields[$key . '_label'] = ['label' => 'Подпись в круге (две латинские буквы)', 'required' => true, 'max' => 2, 'default' => $network['label']];
    $socialFields[$key . '_url'] = ['label' => 'URL (пусто — заглушка)', 'type' => 'url', 'max' => 2000];
    $socialFields[$key . '_visible'] = ['label' => 'Показывать на сайте', 'type' => 'checkbox', 'wide' => true, 'default' => 1];
}

return [
    'orders' => ['title' => 'Приём заказов', 'singleton' => true, 'fields' => [
        'enabled' => ['label' => 'Принимать заказы (после проверки реквизитов и условий)', 'type' => 'checkbox'],
        'pause_message' => ['label' => 'Сообщение при паузе', 'default' => 'Приём заказов временно приостановлен.', 'required' => true, 'max' => 300],
        'minimum' => ['label' => 'Минимальная сумма товаров, ₽', 'type' => 'number', 'min' => 0, 'max' => 1000000, 'default' => 0, 'required' => true],
        'cutoff' => ['label' => 'Последний приём за минут до закрытия', 'type' => 'number', 'min' => 0, 'max' => 180, 'default' => 30, 'required' => true],
        'horizon' => ['label' => 'Выбор даты вперёд, дней', 'type' => 'number', 'min' => 1, 'max' => 90, 'default' => 30, 'required' => true],
        'company' => ['label' => 'Полное наименование ИП / ООО', 'max' => 300],
        'inn' => ['label' => 'ИНН', 'max' => 12],
        'privacy_email' => ['label' => 'E-mail по вопросам персональных данных', 'max' => 254],
    ]],
    'social' => ['title' => 'Социальные сети', 'singleton' => true, 'fields' => $socialFields],
    'categories' => ['title' => 'Категории меню', 'fields' => [
        'name' => ['label' => 'Название', 'required' => true, 'max' => 120],
    ]],
    'products' => ['title' => 'Товары', 'fields' => [
        'name' => ['label' => 'Название', 'required' => true, 'max' => 180],
        'category' => ['label' => 'Категория', 'type' => 'select', 'required' => true],
        'order_type' => ['label' => 'Тип для оформления', 'type' => 'select', 'default' => 'prepared', 'options' => ['prepared' => 'Готовый напиток / десерт', 'beans' => 'Кофе в зёрнах', 'merch' => 'Мерч']],
        'description' => ['label' => 'Описание товара', 'type' => 'textarea', 'required' => true, 'max' => 2000],
        'ingredients' => ['label' => 'Состав', 'type' => 'textarea', 'required' => true, 'max' => 2000],
        'portion' => ['label' => 'Объём, вес или размер (например, 300 мл)', 'required' => true, 'max' => 80],
        'price' => ['label' => 'Цена, ₽', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 1000000],
        'available' => ['label' => 'В наличии', 'type' => 'checkbox', 'default' => 1],
        'image' => ['label' => 'Фотография', 'type' => 'image', 'required' => true],
        'alt' => ['label' => 'Описание фотографии для незрячих', 'required' => true, 'max' => 300],
    ]],
    'contacts' => ['title' => 'Контакты', 'singleton' => true, 'fields' => [
        'name' => ['label' => 'Название кофейни', 'required' => true, 'max' => 120],
        'city' => ['label' => 'Город', 'required' => true, 'max' => 120],
        'street' => ['label' => 'Адрес', 'required' => true, 'max' => 250],
        'phone' => ['label' => 'Телефон', 'type' => 'tel', 'required' => true, 'max' => 80],
        'map_url' => ['label' => 'Ссылка на карту (только для реального адреса)', 'type' => 'url', 'max' => 2000],
    ]],
    'hours' => ['title' => 'Обычный график', 'fixed' => true, 'fields' => [
        'opens' => ['label' => 'Открытие', 'type' => 'time'],
        'closes' => ['label' => 'Закрытие', 'type' => 'time'],
        'closed' => ['label' => 'Выходной', 'type' => 'checkbox'],
    ]],
    'exceptions' => ['title' => 'Особый график', 'fields' => [
        'date' => ['label' => 'Дата', 'type' => 'date', 'required' => true],
        'opens' => ['label' => 'Открытие', 'type' => 'time'],
        'closes' => ['label' => 'Закрытие', 'type' => 'time'],
        'closed' => ['label' => 'Закрыто в этот день', 'type' => 'checkbox'],
        'note' => ['label' => 'Пояснение', 'max' => 300],
    ]],
    'delivery' => ['title' => 'Самовывоз и доставка', 'singleton' => true, 'fields' => [
        'pickup_enabled' => ['label' => 'Самовывоз доступен', 'type' => 'checkbox'],
        'delivery_enabled' => ['label' => 'Доставка доступна', 'type' => 'checkbox'],
        'fee' => ['label' => 'Стоимость доставки, ₽', 'type' => 'number', 'min' => 0, 'max' => 1000000, 'required' => true],
        'threshold' => ['label' => 'Бесплатная доставка от, ₽ (0 — всегда бесплатно)', 'type' => 'number', 'min' => 0, 'max' => 1000000, 'required' => true],
        'zone' => ['label' => 'Зона доставки', 'required' => true, 'max' => 300],
        'pickup_time' => ['label' => 'Ориентир самовывоза', 'required' => true, 'max' => 120],
        'delivery_time' => ['label' => 'Ориентир доставки', 'required' => true, 'max' => 120],
    ]],
    'navigation' => ['title' => 'Навигация сайта', 'fields' => [
        'name' => ['label' => 'Подпись пункта', 'required' => true, 'max' => 100],
        'target' => ['label' => 'Раздел страницы', 'type' => 'select', 'required' => true],
    ]],
    'content' => ['title' => 'Тексты блоков', 'fixed' => true, 'fields' => [
        'text' => ['label' => 'Текст (без HTML)', 'type' => 'textarea', 'required' => true, 'max' => 5000],
    ]],
    'images' => ['title' => 'Фотографии блоков', 'fixed' => true, 'fields' => [
        'name' => ['label' => 'Название в админке', 'required' => true, 'max' => 200],
        'image' => ['label' => 'Фотография', 'type' => 'image', 'required' => true],
        'alt' => ['label' => 'Описание фотографии', 'required' => true, 'max' => 300],
    ]],
    'faq' => ['title' => 'Вопросы и ответы', 'fields' => [
        'question' => ['label' => 'Вопрос', 'required' => true, 'max' => 300],
        'mode' => ['label' => 'Ответ', 'type' => 'select', 'options' => ['manual' => 'Текст ниже', 'hours' => 'Из расписания', 'delivery' => 'Из тарифов доставки', 'timing' => 'Из сроков получения']],
        'prefix' => ['label' => 'Вступление перед автоматическим ответом (необязательно)', 'type' => 'textarea', 'max' => 1000],
        'answer' => ['label' => 'Свободный ответ (используется только в режиме «Текст ниже»)', 'type' => 'textarea', 'max' => 5000],
    ]],
    'announcements' => ['title' => 'Объявления', 'fields' => [
        'text' => ['label' => 'Текст объявления', 'type' => 'textarea', 'required' => true, 'max' => 1000],
        'starts' => ['label' => 'Начало (местное время, необязательно)', 'type' => 'datetime-local'],
        'ends' => ['label' => 'Окончание включительно (местное время, необязательно)', 'type' => 'datetime-local'],
    ]],
    'reviews' => ['title' => 'Отзывы гостей', 'fields' => [
        'name' => ['label' => 'Автор', 'required' => true, 'max' => 150],
        'text' => ['label' => 'Текст отзыва', 'type' => 'textarea', 'required' => true, 'max' => 3000],
        'source' => ['label' => 'Ссылка на реальный источник (обязательна для реального отзыва)', 'type' => 'url', 'max' => 2000],
        'image' => ['label' => 'Фото автора (необязательно)', 'type' => 'image'],
        'alt' => ['label' => 'Описание фотографии', 'max' => 300],
        'is_demo' => ['label' => 'Демо-отзыв: вымышленный текст и автор, с явной подписью на сайте', 'type' => 'checkbox'],
    ]],
    'rating' => ['title' => 'Рейтинг из внешнего источника', 'singleton' => true, 'fields' => [
        'value' => ['label' => 'Рейтинг от 0 до 5 (например, 4.9; пусто — не показывать)', 'max' => 4],
        'count' => ['label' => 'Количество оценок', 'type' => 'number', 'min' => 0, 'max' => 10000000],
        'source' => ['label' => 'Ссылка на источник рейтинга', 'type' => 'url', 'max' => 2000],
    ]],
    'seo' => ['title' => 'SEO и превью ссылки', 'singleton' => true, 'fields' => [
        'title' => ['wide' => true, 'label' => 'Заголовок страницы (title)', 'required' => true, 'max' => 200, 'hint' => 'Ориентир: до 60 символов.', 'count' => true],
        'description' => ['wide' => true, 'label' => 'Описание для поисковиков (meta description)', 'type' => 'textarea', 'rows' => 3, 'required' => true, 'max' => 500, 'hint' => 'Ориентир: 50–160 символов.', 'count' => true],
        'og_title' => ['wide' => true, 'label' => 'Заголовок превью ссылки', 'required' => true, 'max' => 200, 'hint' => 'Ориентир: до 60 символов.', 'count' => true],
        'og_description' => ['wide' => true, 'label' => 'Описание превью ссылки', 'type' => 'textarea', 'rows' => 3, 'required' => true, 'max' => 500, 'hint' => 'Ориентир: до 160 символов.', 'count' => true],
        'schema_description' => ['wide' => true, 'label' => 'Описание для разметки schema.org', 'type' => 'textarea', 'rows' => 3, 'max' => 500, 'hint' => 'Ориентир: до 200 символов. Пустое поле использует описание для поисковиков.', 'count' => true],
        'image' => ['wide' => true, 'label' => 'Изображение превью (после загрузки JPG 1200×630)', 'type' => 'image', 'required' => true],
        'image_alt' => ['wide' => true, 'label' => 'Что на картинке', 'max' => 300, 'hint' => 'Для незрячих: опишите изображение словами. Пустое поле использует заголовок превью.'],
    ]],
];
