<?php
declare(strict_types=1);

function ordersInstalled(): bool
{
    return (bool) database()->query("SELECT name FROM sqlite_master WHERE type='table' AND name='orders'")->fetchColumn();
}

function findOrder(int $id): ?array
{
    $query = database()->prepare('SELECT * FROM orders WHERE id=?');
    $query->execute([$id]);
    $order = $query->fetch();
    if (!$order) return null;
    $order['customer'] = json_decode($order['customer'], true, 32, JSON_THROW_ON_ERROR);
    $order['tariff'] = json_decode($order['tariff'], true, 32, JSON_THROW_ON_ERROR);
    foreach (['order_items' => 'items', 'order_events' => 'events'] as $table => $key) {
        $query = database()->prepare('SELECT * FROM ' . $table . ' WHERE order_id=? ORDER BY id');
        $query->execute([$id]);
        $order[$key] = $query->fetchAll();
    }
    $order['number'] = 'VP-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    $order['contact_overdue'] = $order['next_contact'] !== '' && $order['next_contact'] < date('Y-m-d\TH:i') && !in_array($order['status'], ['delivered', 'collected', 'cancelled'], true);
    return $order;
}

function orderByRequestKey(string $key): ?array
{
    $query = database()->prepare('SELECT id FROM orders WHERE request_key=?');
    $query->execute([$key]);
    $id = $query->fetchColumn();
    return $id ? findOrder((int) $id) : null;
}

function orderEvent(int $id, string $message, string $actor = 'manager'): void
{
    database()->prepare('INSERT INTO order_events(order_id,created_at,actor,message) VALUES(?,?,?,?)')
        ->execute([$id, date('Y-m-d H:i:s'), $actor, $message]);
}

function storeOrderItems(int $id, array $items): void
{
    database()->prepare('DELETE FROM order_items WHERE order_id=?')->execute([$id]);
    $insert = database()->prepare('INSERT INTO order_items(order_id,product_id,name,portion,price,quantity,comment) VALUES(?,?,?,?,?,?,?)');
    foreach ($items as $item) $insert->execute([$id, $item['product_id'], $item['name'], $item['portion'], $item['price'], $item['quantity'], $item['comment']]);
}

function insertOrder(array $customer, array $quote, string $receiving, string $key): array
{
    $pdo = database();
    $pdo->beginTransaction();
    try {
        $existing = orderByRequestKey($key);
        if ($existing) { $pdo->commit(); return $existing; }
        $now = date('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO orders(token,request_key,receiving,customer,tariff,subtotal,delivery_fee,total,requested_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([bin2hex(random_bytes(32)), $key, $receiving, json_encode($customer, JSON_UNESCAPED_UNICODE), json_encode($quote['tariff']), $quote['subtotal'], $quote['fee'], $quote['total'], $customer['requested_at'], $now, $now]);
        $id = (int) $pdo->lastInsertId();
        storeOrderItems($id, $quote['items']);
        orderEvent($id, 'Заказ получен', 'customer');
        foreach (['manager', 'customer'] as $kind) {
            if ($kind === 'customer' && $customer['email'] === '') continue;
            $pdo->prepare('INSERT INTO order_mail_queue(order_id,recipient_kind) VALUES(?,?)')->execute([$id, $kind]);
        }
        $pdo->commit();
        return findOrder($id);
    } catch (Throwable $error) { $pdo->rollBack(); throw $error; }
}

function updateOrderRecord(array $order, array $changes, string $message, ?array $items = null): void
{
    $pdo = database();
    $pdo->beginTransaction();
    try {
        $sets = [];
        $values = [];
        foreach (['status', 'customer', 'subtotal', 'delivery_fee', 'total', 'requested_at', 'agreed_at', 'next_contact', 'finished_at'] as $field) {
            if (!array_key_exists($field, $changes)) continue;
            $sets[] = $field . '=?';
            $values[] = is_array($changes[$field]) ? json_encode($changes[$field], JSON_UNESCAPED_UNICODE) : $changes[$field];
        }
        $sets[] = 'version=version+1';
        $sets[] = 'updated_at=?';
        array_push($values, date('Y-m-d H:i:s'), $order['id'], $order['version']);
        $query = $pdo->prepare('UPDATE orders SET ' . implode(',', $sets) . ' WHERE id=? AND version=? AND anonymized=0');
        $query->execute($values);
        if ($query->rowCount() !== 1) throw new DomainException('Заказ изменён в другой вкладке. Обновите страницу.');
        if ($items !== null) storeOrderItems((int) $order['id'], $items);
        orderEvent((int) $order['id'], $message);
        $pdo->commit();
    } catch (Throwable $error) { $pdo->rollBack(); throw $error; }
}

function searchOrders(array $filter, int $page = 1, int $limit = 30, bool $oldest = false): array
{
    $where = ['1=1']; $values = [];
    foreach (['status', 'receiving'] as $field) if (!empty($filter[$field])) { $where[] = $field . '=?'; $values[] = $filter[$field]; }
    if (!empty($filter['active'])) $where[] = "status NOT IN ('delivered','collected','cancelled')";
    if (!empty($filter['q'])) {
        $where[] = "(CAST(id AS TEXT) LIKE ? OR customer LIKE ?)";
        $term = '%' . str_replace('VP-', '', $filter['q']) . '%';
        array_push($values, $term, $term);
    }
    foreach (['from' => '>=', 'to' => '<='] as $field => $operator) if (!empty($filter[$field])) {
        $where[] = 'created_at ' . $operator . ' ?'; $values[] = $filter[$field] . ($field === 'to' ? ' 23:59:59' : ' 00:00:00');
    }
    $sql = ' FROM orders WHERE ' . implode(' AND ', $where);
    $query = database()->prepare('SELECT COUNT(*)' . $sql); $query->execute($values); $total = (int) $query->fetchColumn();
    $query = database()->prepare('SELECT id' . $sql . ' ORDER BY id ' . ($oldest ? 'ASC' : 'DESC') . ' LIMIT ? OFFSET ?');
    foreach ($values as $index => $value) $query->bindValue($index + 1, $value);
    $query->bindValue(count($values) + 1, $limit, PDO::PARAM_INT);
    $query->bindValue(count($values) + 2, (max(1, $page) - 1) * $limit, PDO::PARAM_INT);
    $query->execute();
    return ['total' => $total, 'rows' => array_map(fn ($id) => findOrder((int) $id), $query->fetchAll(PDO::FETCH_COLUMN))];
}

function orderNewCount(): int
{
    return ordersInstalled() ? (int) database()->query("SELECT COUNT(*) FROM orders WHERE status='new'")->fetchColumn() : 0;
}

function orderRateLimit(string $ip): void
{
    $key = hash('sha256', $ip);
    $now = time();
    database()->prepare('DELETE FROM order_attempts WHERE started<?')->execute([$now - 3600]);
    database()->prepare('INSERT INTO order_attempts(ip_hash,started,attempts) VALUES(?,?,1) ON CONFLICT(ip_hash) DO UPDATE SET attempts=attempts+1')->execute([$key, $now]);
    $query = database()->prepare('SELECT attempts FROM order_attempts WHERE ip_hash=?'); $query->execute([$key]);
    if ($query->fetchColumn() > 20) throw new InvalidArgumentException('Слишком много запросов. Повторите через час.', 429);
}
