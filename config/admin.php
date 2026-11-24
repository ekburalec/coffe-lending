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
];
