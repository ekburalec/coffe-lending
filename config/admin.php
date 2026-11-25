<?php
declare(strict_types=1);

return [
    'categories' => ['title' => 'Категории меню', 'fields' => [
        'name' => ['label' => 'Название', 'required' => true, 'max' => 120],
    ]],
    'products' => ['title' => 'Товары', 'fields' => [
        'name' => ['label' => 'Название', 'required' => true, 'max' => 180],
        'category' => ['label' => 'Категория', 'type' => 'select', 'required' => true],
        'description' => ['label' => 'Описание вкуса', 'type' => 'textarea', 'required' => true, 'max' => 2000],
        'ingredients' => ['label' => 'Состав', 'type' => 'textarea', 'required' => true, 'max' => 2000],
        'portion' => ['label' => 'Порция (например, 300 мл)', 'required' => true, 'max' => 80],
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
    'reviews' => ['title' => 'Реальные отзывы', 'fields' => [
        'name' => ['label' => 'Автор', 'required' => true, 'max' => 150],
        'text' => ['label' => 'Текст отзыва', 'type' => 'textarea', 'required' => true, 'max' => 3000],
        'source' => ['label' => 'Ссылка на реальный источник', 'type' => 'url', 'required' => true, 'max' => 2000],
    ]],
    'rating' => ['title' => 'Рейтинг из внешнего источника', 'singleton' => true, 'fields' => [
        'value' => ['label' => 'Рейтинг от 0 до 5 (например, 4.9; пусто — не показывать)', 'max' => 4],
        'count' => ['label' => 'Количество оценок', 'type' => 'number', 'min' => 0, 'max' => 10000000],
        'source' => ['label' => 'Ссылка на источник рейтинга', 'type' => 'url', 'max' => 2000],
    ]],
    'seo' => ['title' => 'SEO и превью ссылки', 'singleton' => true, 'fields' => [
        'title' => ['wide' => true, 'label' => 'Заголовок страницы (title)', 'required' => true, 'max' => 200],
        'description' => ['wide' => true, 'label' => 'Описание страницы', 'type' => 'textarea', 'required' => true, 'max' => 500],
        'og_title' => ['wide' => true, 'label' => 'Заголовок превью ссылки', 'required' => true, 'max' => 200],
        'og_description' => ['wide' => true, 'label' => 'Описание превью ссылки', 'type' => 'textarea', 'required' => true, 'max' => 500],
        'image' => ['wide' => true, 'label' => 'Изображение превью (после загрузки JPG 1200×630)', 'type' => 'image', 'required' => true],
    ]],
];
