<?php
declare(strict_types=1);

function checkoutController(): void
{
    startAdminSession();
    $settings = orderSettings();
    $errors = []; $quote = null;
    $customer = $_SESSION['order_customer'] ?? [];
    $receiving = $_SESSION['order_receiving'] ?? 'pickup';
    $quantities = $_SESSION['order_quantities'] ?? [];
    $comments = $_SESSION['order_comments'] ?? [];
    $_SESSION['order_key'] ??= bin2hex(random_bytes(32));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validCsrf()) { http_response_code(403); $errors['_form'] = 'Форма устарела. Обновите страницу.'; }
        else {
            try {
                $action = postText('action');
                if ($action === 'place') {
                    $existing = orderByRequestKey(postText('request_key'));
                    if ($existing && in_array($existing['token'], $_SESSION['order_tokens'] ?? [], true)) redirect('/order-success');
                }
                if (isset($_POST['quantities'])) {
                    if (!is_array($_POST['quantities'])) throw new InvalidArgumentException('Некорректный состав.');
                    $quantities = $_POST['quantities'];
                    $comments = is_array($_POST['comments'] ?? null) ? $_POST['comments'] : [];
                }
                $receiving = postText('receiving') ?: $receiving;
                $quote = orderQuote($quantities, $receiving, $comments);
                $_SESSION['order_quantities'] = $quantities;
                $_SESSION['order_comments'] = $comments;
                $_SESSION['order_receiving'] = $receiving;
                if ($action === 'place') {
                    if (!hash_equals($_SESSION['order_key'], postText('request_key'))) throw new InvalidArgumentException('Обновите форму заказа.');
                    if (empty($settings['enabled'])) throw new InvalidArgumentException($settings['pause_message']);
                    orderRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown');
                    if (postText('website') !== '') throw new InvalidArgumentException('Не удалось принять форму.');
                    [$customer, $errors] = validateOrderCustomer($_POST, $receiving);
                    $_SESSION['order_customer'] = $customer;
                    if (postText('consent') !== '1') $errors['consent'] = 'Подтвердите согласие на обработку данных для заказа.';
                    if (!hash_equals($quote['fingerprint'], postText('fingerprint'))) $errors['_form'] = 'Состав, цены или доставка изменились. Проверьте новый итог и повторите подтверждение.';
                    if (!$errors) orderAcceptsTime($customer['requested_at'], $quote['scheduled_allowed']);
                    if (!$errors) {
                        $order = insertOrder($customer, $quote, $receiving, $_SESSION['order_key']);
                        $_SESSION['order_tokens'] = [$order['token']]; $_SESSION['last_order_id'] = $order['id'];
                        unset($_SESSION['order_quantities'], $_SESSION['order_comments'], $_SESSION['order_customer'], $_SESSION['order_key']);
                        redirect('/order-success');
                    }
                }
            } catch (InvalidArgumentException $error) { $errors['_form'] = $error->getMessage(); if ($error->getCode() === 429) http_response_code(429); }
            if ($errors && http_response_code() !== 429) http_response_code(422);
        }
    }
    if (!$quote && $quantities) try { $quote = orderQuote($quantities, $receiving, $comments); } catch (InvalidArgumentException $error) { $errors['_form'] = $error->getMessage(); }
    $products = [];
    $categories = array_column(listRecords('categories', true), 'id');
    foreach (listRecords('products', true) as $product) if (!empty($product['available']) && in_array($product['category'], $categories, true)) $products[] = $product;
    $definitions = orderCustomerFields();
    if ($receiving === 'pickup') foreach (['street', 'house', 'apartment', 'entrance', 'floor', 'intercom'] as $key) unset($definitions[$key]);
    else foreach (['street', 'house'] as $key) $definitions[$key]['required'] = true;
    if (!$quote || !$quote['scheduled_allowed']) unset($definitions['requested_at']);
    $fields = adminFields($definitions, $customer, $errors, 'checkout');
    render('checkout', ['title' => 'Оформление заказа', 'csrf' => $_SESSION['csrf'], 'requestKey' => $_SESSION['order_key'],
        'quote' => $quote, 'products' => $products, 'quantities' => $quantities, 'comments' => $comments, 'receiving' => $receiving,
        'pickupAddress' => getSettings('contacts')['street'], 'fields' => $fields, 'errors' => $errors, 'settings' => $settings]);
}

function orderSuccessController(): void
{
    startAdminSession();
    $order = findOrder((int) ($_SESSION['last_order_id'] ?? 0));
    if (!$order || !in_array($order['token'], $_SESSION['order_tokens'] ?? [], true)) { http_response_code(404); render('error', ['title' => 'Заказ не найден', 'message' => 'Вернитесь к меню.']); return; }
    render('order-success', ['order' => $order]);
}

function privacyController(): void
{
    header('X-Robots-Tag: noindex');
    render('privacy', ['settings' => orderSettings()]);
}
