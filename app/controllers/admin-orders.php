<?php
declare(strict_types=1);

function adminOrdersController(): void
{
    requireAdmin();
    if (!ordersInstalled()) { adminPage('Заказы', 'orders', ['installed' => false]); return; }
    $id = max(0, (int) ($_GET['id'] ?? 0));
    $order = $id ? findOrder($id) : null;
    if ($id && !$order) { http_response_code(404); render('error', ['title' => 'Заказ не найден', 'message' => 'Вернитесь к списку.']); return; }
    $errors = []; $values = $order['customer'] ?? [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrf()) { http_response_code(403); $errors['_form'] = 'Форма устарела.'; }
        else try {
            if (!$order || (int) postText('version') !== (int) $order['version']) throw new DomainException('Заказ изменён. Обновите страницу.');
            $action = postText('action');
            if ($action === 'status') changeOrderStatus($order, postText('status'), postText('reason'), postText('correction') === '1');
            elseif ($action === 'note') {
                if ($order['anonymized'] || postText('note') === '' || mb_strlen(postText('note')) > 1000) throw new InvalidArgumentException('Укажите заметку до 1000 символов.');
                $nextContact = postText('next_contact');
                if ($nextContact !== '' && !validOrderDateTime($nextContact)) throw new InvalidArgumentException('Проверьте время следующего контакта.');
                updateOrderRecord($order, ['next_contact' => $nextContact], postText('note'));
            } elseif ($action === 'edit') {
                if (!editableOrder($order)) throw new InvalidArgumentException('На этом этапе состав и контакты уже нельзя менять.');
                [$values, $errors] = validateOrderCustomer($_POST, $order['receiving']);
                $quantities = $_POST['quantities'] ?? [];
                if (!is_array($quantities)) throw new InvalidArgumentException('Некорректный состав.');
                $quote = orderQuote($quantities, $order['receiving'], is_array($_POST['comments'] ?? null) ? $_POST['comments'] : [], $order);
                if ($values['requested_at'] !== '') orderAcceptsTime($values['requested_at'], $quote['scheduled_allowed']);
                $agreed = postText('agreed_at');
                if ($agreed !== '' && !validOrderDateTime($agreed)) throw new InvalidArgumentException('Проверьте согласованное время.');
                if (!$errors) updateOrderRecord($order, ['customer' => $values, 'requested_at' => $values['requested_at'], 'agreed_at' => $agreed,
                    'subtotal' => $quote['subtotal'], 'delivery_fee' => $quote['fee'], 'total' => $quote['total']], orderEditSummary($order, $values, $quote), $quote['items']);
            } elseif ($action === 'retry_mail') {
                retryOrderMail($id);
                updateOrderRecord($order, [], 'Неудачные письма возвращены в очередь');
            } else throw new InvalidArgumentException('Неизвестное действие.');
            if (!$errors) redirect('/admin/orders?id=' . $id . '&saved=1');
        } catch (DomainException $error) { http_response_code(409); $errors['_form'] = $error->getMessage(); }
        catch (InvalidArgumentException $error) { http_response_code(422); $errors['_form'] = $error->getMessage(); }
    }
    $statuses = orderStatusLabels();
    if ($order) {
        $fields = adminFields(orderCustomerFields(), $values, $errors, 'order');
        $products = listRecords('products');
        $productRows = array_column($order['items'], null, 'product_id');
        foreach ($products as $product) if (!isset($productRows[$product['id']])) $productRows[$product['id']] = ['product_id' => $product['id'], 'name' => $product['name'], 'quantity' => 0, 'price' => $product['price'], 'comment' => ''];
        $mailRows = orderMailRows($id);
        $canEdit = editableOrder($order);
        adminPage($order['number'], 'order', compact('order', 'fields', 'errors', 'statuses', 'productRows', 'mailRows', 'canEdit'));
        return;
    }
    $filter = [];
    foreach (['q', 'status', 'receiving', 'from', 'to'] as $key) $filter[$key] = is_string($_GET[$key] ?? null) ? mb_substr($_GET[$key], 0, 100) : '';
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $board = isset($_GET['board']);
    $columns = [];
    if ($board) {
        $filter['receiving'] = $filter['receiving'] === 'pickup' ? 'pickup' : 'delivery';
        foreach (orderPath($filter['receiving']) as $status) {
            if (in_array($status, ['delivered', 'collected'], true)) continue;
            $columns[$status] = searchOrders(array_merge($filter, ['status' => $status]), 1, 50, true);
        }
    }
    $result = searchOrders($filter, $page);
    $previousUrl = '/admin/orders?' . http_build_query(array_merge($filter, ['page' => max(1, $page - 1)]));
    $nextUrl = '/admin/orders?' . http_build_query(array_merge($filter, ['page' => $page + 1]));
    adminPage('Заказы', 'orders', compact('filter', 'result', 'page', 'board', 'columns', 'statuses', 'previousUrl', 'nextUrl') + ['installed' => true]);
}

function validOrderDateTime(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
    return $date && $date->format('Y-m-d\TH:i') === $value;
}

function adminOrderCountController(): void
{
    requireAdmin();
    header('Content-Type: application/json');
    echo json_encode(['count' => orderNewCount(), 'time' => date('H:i:s')]);
}

function adminOrderMoveController(): void
{
    requireAdmin();
    header('Content-Type: application/json');
    if (!validCsrf()) { http_response_code(403); echo json_encode(['error' => 'Форма устарела. Обновите страницу.']); return; }
    try {
        $order = findOrder((int) postText('id'));
        if (!$order || (int) $order['version'] !== (int) postText('version')) throw new DomainException('Заказ изменён. Обновите доску.');
        changeOrderStatus($order, postText('status'));
        echo json_encode(['version' => $order['version'] + 1]);
    } catch (InvalidArgumentException|DomainException $error) { http_response_code(409); echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE); }
}
