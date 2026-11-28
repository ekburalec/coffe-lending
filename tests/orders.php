<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core/bootstrap.php';
function checkOrder(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$q = orderQuote(['americano' => 2], 'pickup');
checkOrder($q['subtotal'] === 380 && $q['fee'] === 0, 'Server quote');
[$customer, $errors] = validateOrderCustomer(['first_name' => 'Тест', 'phone' => '+7 900 123-45-67'], 'pickup');
checkOrder(!$errors, 'Optional customer fields');
$key = bin2hex(random_bytes(32));
$order = insertOrder($customer, $q, 'pickup', $key);
checkOrder(insertOrder($customer, $q, 'pickup', $key)['id'] === $order['id'], 'Idempotency');
changeOrderStatus($order, 'contacted');
try { changeOrderStatus($order, 'contacted'); throw new RuntimeException('No conflict'); } catch (DomainException $expected) {}
$order = findOrder((int) $order['id']);
try { changeOrderStatus($order, 'courier'); throw new RuntimeException('Wrong receiving path'); } catch (InvalidArgumentException $expected) {}
changeOrderStatus($order, 'cancelled', 'Тестовая отмена');
checkOrder(findOrder((int) $order['id'])['status'] === 'cancelled', 'Cancellation');
try { orderQuote(['americano' => -1], 'pickup'); throw new RuntimeException('Negative quantity'); } catch (InvalidArgumentException $expected) {}
echo "PASS: quotes, validation, idempotency, conflicts, statuses, quantities\n";

// Реальные часы и исключения проверяем с явным временем, независимо от запуска теста.
$now = new DateTimeImmutable('2026-11-28 12:00:00');
orderAcceptsTime('', false, $now);
try { orderAcceptsTime('2026-11-29T12:00', false, $now); throw new RuntimeException('Prepared future time accepted'); } catch (InvalidArgumentException $expected) {}
orderAcceptsTime('2026-11-29T12:00', true, $now);
try { orderAcceptsTime('2026-11-29T03:00', true, $now); throw new RuntimeException('Closed hours accepted'); } catch (InvalidArgumentException $expected) {}
$product = findRecord('products', 'americano');
$snapshot = $product;
$product['price'] = 999;
saveRecord('products', 'americano', $product);
$freshQuote = orderQuote(['americano' => 2], 'pickup');
checkOrder($freshQuote['fingerprint'] !== $q['fingerprint'], 'Price change invalidates confirmation');
$oldQuote = orderQuote(['americano' => 3], 'pickup', [], $order);
checkOrder($oldQuote['items'][0]['price'] === 190, 'Manager preserves old price');
$product['order_type'] = 'beans';
saveRecord('products', 'americano', $product);
checkOrder(orderQuote(['americano' => 1], 'pickup')['scheduled_allowed'], 'Beans scheduling');
checkOrder(!orderQuote(['americano' => 1, 'cappuccino' => 1], 'pickup')['scheduled_allowed'], 'Mixed cart immediate');
saveRecord('products', 'americano', $snapshot);
[$deliveryCustomer, $deliveryErrors] = validateOrderCustomer(['first_name' => 'Тест', 'phone' => '+79001234567'], 'delivery');
checkOrder(isset($deliveryErrors['street'], $deliveryErrors['house']), 'Delivery requires address');
echo "PASS: hours, future dates, closed hours, changed prices, snapshots, beans, mixed cart, address\n";
