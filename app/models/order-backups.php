<?php
declare(strict_types=1);

function validateRestoredOrders(PDO $pdo): void
{
    foreach ($pdo->query('SELECT * FROM orders') as $order) {
        $customer = json_decode($order['customer'], true, 32, JSON_THROW_ON_ERROR);
        $tariff = json_decode($order['tariff'], true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($customer) || !is_array($tariff) || !in_array($order['receiving'], ['delivery', 'pickup'], true)
            || !in_array($order['status'], [...orderPath($order['receiving']), 'cancelled'], true)
            || !preg_match('/^[a-f0-9]{64}$/D', $order['token']) || !preg_match('/^[a-f0-9]{64}$/D', $order['request_key'])) throw new InvalidArgumentException('Некорректный заказ в копии.');
        foreach (['fee', 'threshold'] as $key) if (!isset($tariff[$key]) || !is_int($tariff[$key]) || $tariff[$key] < 0) throw new InvalidArgumentException('Некорректный тариф заказа в копии.');
        if (!$order['anonymized']) {
            [, $errors] = validateOrderCustomer($customer, $order['receiving']);
            if ($errors) throw new InvalidArgumentException('Некорректные контакты в копии.');
        }
        $query = $pdo->prepare('SELECT price,quantity,comment FROM order_items WHERE order_id=?');
        $query->execute([$order['id']]); $subtotal = 0; $count = 0;
        foreach ($query as $item) {
            if ($item['price'] < 0 || $item['quantity'] < 1 || $item['quantity'] > 99 || mb_strlen($item['comment']) > 500) throw new InvalidArgumentException('Некорректная позиция в копии.');
            $subtotal += $item['price'] * $item['quantity']; $count++;
        }
        if (!$count || $count > 50 || $subtotal !== $order['subtotal'] || $order['delivery_fee'] < 0 || $order['total'] !== $subtotal + $order['delivery_fee']) throw new InvalidArgumentException('Несогласованные суммы заказа в копии.');
    }
}
