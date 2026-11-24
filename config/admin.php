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
];
