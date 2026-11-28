<?php
declare(strict_types=1);

function orderSettings(): array
{
    return array_merge(['enabled' => 0, 'minimum' => 0, 'cutoff' => 30, 'horizon' => 30,
        'pause_message' => 'Приём заказов временно приостановлен.', 'company' => '', 'inn' => '', 'privacy_email' => ''], getSettings('orders'));
}

function orderStatusLabels(): array
{
    return ['new' => 'Новый', 'contacted' => 'Связались с клиентом', 'processing' => 'В обработке',
        'courier' => 'Передан курьеру', 'ready' => 'Готов к выдаче', 'delivered' => 'Доставлен', 'collected' => 'Выдан', 'cancelled' => 'Отменён'];
}

function orderPath(string $receiving): array
{
    return $receiving === 'delivery' ? ['new', 'contacted', 'processing', 'courier', 'delivered'] : ['new', 'contacted', 'processing', 'ready', 'collected'];
}

function orderQuote(array $quantities, string $receiving, array $comments = [], ?array $previous = null): array
{
    if (!in_array($receiving, ['pickup', 'delivery'], true)) throw new InvalidArgumentException('Выберите способ получения.');
    if (count($quantities) > 50) throw new InvalidArgumentException('В заказе может быть до 50 позиций.');
    $tariff = $previous['tariff'] ?? getSettings('delivery');
    if (!$previous && empty($tariff[$receiving === 'delivery' ? 'delivery_enabled' : 'pickup_enabled'])) throw new InvalidArgumentException('Этот способ получения сейчас недоступен.');
    $oldItems = array_column($previous['items'] ?? [], null, 'product_id');
    $categories = array_column(listRecords('categories', true), 'id');
    $items = []; $subtotal = 0; $scheduledAllowed = true;
    foreach ($quantities as $id => $raw) {
        if (!is_scalar($raw) || filter_var($raw, FILTER_VALIDATE_INT) === false) throw new InvalidArgumentException('Некорректное количество.');
        $quantity = (int) $raw;
        if ($quantity === 0) continue;
        if ($quantity < 1 || $quantity > 99) throw new InvalidArgumentException('Количество должно быть от 1 до 99.');
        $product = findRecord('products', (string) $id);
        $old = $oldItems[$id] ?? null;
        if (!$old && (!$product || empty($product['visible']) || empty($product['available']) || !in_array($product['category'], $categories, true))) throw new InvalidArgumentException('Товар недоступен. Обновите состав.');
        if ($old && $quantity > $old['quantity'] && (!$product || empty($product['visible']) || empty($product['available']) || !in_array($product['category'], $categories, true))) throw new InvalidArgumentException('Нельзя увеличить количество недоступного товара.');
        $comment = $comments[$id] ?? '';
        if (!is_string($comment) || mb_strlen($comment) > 500) throw new InvalidArgumentException('Пожелание к позиции: до 500 символов.');
        $item = ['product_id' => (string) $id, 'name' => $old['name'] ?? $product['name'], 'portion' => $old['portion'] ?? $product['portion'],
            'price' => (int) ($old['price'] ?? $product['price']), 'quantity' => $quantity, 'comment' => trim($comment)];
        $items[] = $item; $subtotal += $item['price'] * $quantity;
        if (!in_array($product['order_type'] ?? 'prepared', ['beans', 'merch'], true)) $scheduledAllowed = false;
    }
    if (!$items) throw new InvalidArgumentException('Добавьте хотя бы один товар.');
    if ($subtotal < (int) orderSettings()['minimum']) throw new InvalidArgumentException('Сумма товаров ниже минимальной.');
    $fee = $receiving === 'delivery' && $subtotal < (int) $tariff['threshold'] ? (int) $tariff['fee'] : 0;
    $quote = ['items' => $items, 'subtotal' => $subtotal, 'fee' => $fee, 'total' => $subtotal + $fee, 'tariff' => $tariff, 'scheduled_allowed' => $scheduledAllowed];
    $quote['fingerprint'] = hash('sha256', json_encode([$items, $tariff, $receiving, $fee]));
    return $quote;
}

function orderAcceptsTime(string $requested, bool $scheduledAllowed, ?DateTimeImmutable $now = null): void
{
    $settings = orderSettings();
    $now ??= new DateTimeImmutable();
    $target = $now;
    if ($requested !== '') {
        if (!$scheduledAllowed) throw new InvalidArgumentException('Для напитков и десертов доступно получение только по готовности.');
        $target = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $requested);
        if (!$target || $target->format('Y-m-d\TH:i') !== $requested || $target <= $now || $target > $now->modify('+' . (int) $settings['horizon'] . ' days')) throw new InvalidArgumentException('Выберите будущее время в допустимом диапазоне.');
    }
    $hours = findRecord('hours', $target->format('N'));
    foreach (listRecords('exceptions', true) as $exception) if ($exception['date'] === $target->format('Y-m-d')) $hours = $exception;
    if (!$hours || !empty($hours['closed'])) throw new InvalidArgumentException('В этот день кофейня закрыта.');
    $start = $target->setTime((int) substr($hours['opens'], 0, 2), (int) substr($hours['opens'], 3, 2));
    $end = $target->setTime((int) substr($hours['closes'], 0, 2), (int) substr($hours['closes'], 3, 2))->modify('-' . (int) $settings['cutoff'] . ' minutes');
    if ($target < $start || $target >= $end) throw new InvalidArgumentException('Выберите время приёма в рабочие часы, с учётом времени до закрытия.');
}

function changeOrderStatus(array $order, string $next, string $reason = '', bool $correction = false): void
{
    $path = orderPath($order['receiving']);
    if (!in_array($next, [...$path, 'cancelled'], true) || $next === $order['status']) throw new InvalidArgumentException('Недопустимый переход.');
    $terminal = in_array($order['status'], ['delivered', 'collected', 'cancelled'], true);
    $position = array_search($order['status'], $path, true);
    $forward = !$terminal && ($next === 'cancelled' || ($position !== false && ($path[$position + 1] ?? '') === $next));
    if (!$forward && (!$correction || trim($reason) === '')) throw new InvalidArgumentException('Для исправления статуса укажите пояснение и отметьте возврат/исправление.');
    if (mb_strlen($reason) > 1000) throw new InvalidArgumentException('Пояснение: до 1000 символов.');
    $finished = in_array($next, ['delivered', 'collected', 'cancelled'], true);
    updateOrderRecord($order, ['status' => $next, 'finished_at' => $finished ? date('Y-m-d H:i:s') : null],
        orderStatusLabels()[$order['status']] . ' → ' . orderStatusLabels()[$next] . ($reason !== '' ? ': ' . $reason : ''));
}

function editableOrder(array $order): bool
{
    return !$order['anonymized'] && in_array($order['status'], ['new', 'contacted', 'processing'], true);
}

function orderEditSummary(array $order, array $customer, array $quote): string
{
    $old = array_map(fn ($item) => $item['name'] . ' × ' . $item['quantity'], $order['items']);
    $new = array_map(fn ($item) => $item['name'] . ' × ' . $item['quantity'], $quote['items']);
    $changed = [];
    foreach ($customer as $key => $value) if (($order['customer'][$key] ?? '') !== $value) $changed[] = orderCustomerFields()[$key]['label'] ?? $key;
    return 'Состав: ' . implode(', ', $old) . ' → ' . implode(', ', $new) . '. Итог: ' . rubles($order['total']) . ' → ' . rubles($quote['total']) . ($changed ? '. Изменены поля: ' . implode(', ', $changed) : '');
}
