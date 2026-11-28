<?php
declare(strict_types=1);

function orderCustomerFields(): array
{
    return ['first_name' => ['label' => 'Имя', 'required' => true, 'max' => 100, 'autocomplete' => 'given-name'],
        'last_name' => ['label' => 'Фамилия (необязательно)', 'max' => 100, 'autocomplete' => 'family-name'],
        'phone' => ['label' => 'Телефон', 'type' => 'tel', 'required' => true, 'max' => 40, 'autocomplete' => 'tel'],
        'email' => ['label' => 'Электронная почта (необязательно)', 'type' => 'email', 'max' => 254, 'autocomplete' => 'email'],
        'street' => ['label' => 'Улица (Екатеринбург)', 'max' => 150, 'autocomplete' => 'address-line1'],
        'house' => ['label' => 'Дом', 'max' => 30], 'apartment' => ['label' => 'Квартира / офис', 'max' => 30],
        'entrance' => ['label' => 'Подъезд', 'max' => 30], 'floor' => ['label' => 'Этаж', 'max' => 20],
        'intercom' => ['label' => 'Домофон', 'max' => 50],
        'comment' => ['label' => 'Комментарий к заказу', 'type' => 'textarea', 'max' => 1000],
        'requested_at' => ['label' => 'Желаемые дата и время (только зёрна и мерч)', 'type' => 'datetime-local', 'max' => 16]];
}

function validateOrderCustomer(array $input, string $receiving): array
{
    [$data, $errors] = validateAdminFields(orderCustomerFields(), [], $input, false);
    if (!preg_match('/^\+?[0-9 ()-]{10,40}$/D', $data['phone']) || strlen(preg_replace('/\D/', '', $data['phone'])) < 10 || strlen(preg_replace('/\D/', '', $data['phone'])) > 15) $errors['phone'] = 'Укажите телефон с кодом города или страны.';
    if ($data['email'] !== '' && (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $data['email']))) $errors['email'] = 'Проверьте адрес электронной почты.';
    if ($receiving === 'delivery') {
        foreach (['street', 'house'] as $key) if ($data[$key] === '') $errors[$key] = 'Заполните адрес доставки.';
    } else {
        foreach (['street', 'house', 'apartment', 'entrance', 'floor', 'intercom'] as $key) $data[$key] = '';
    }
    return [$data, $errors];
}
