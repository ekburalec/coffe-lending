<?php
declare(strict_types=1);

function smtpSettings(): array
{
    $path = appConfig('data_dir') . '/smtp.json';
    return array_merge(['host' => '127.0.0.1', 'port' => 1025, 'security' => 'none', 'username' => '', 'password' => '',
        'from' => 'coffee@example.test', 'manager' => 'manager@example.test'], is_file($path) ? json_decode(file_get_contents($path), true, 16, JSON_THROW_ON_ERROR) : []);
}

function smtpRead($socket, array $codes): void
{
    $size = 0;
    do {
        $line = fgets($socket, 1024);
        if ($line === false || ($size += strlen($line)) > 16384) throw new RuntimeException('SMTP connection failed');
    } while (isset($line[3]) && $line[3] === '-');
    if (!in_array((int) substr($line, 0, 3), $codes, true)) throw new RuntimeException('SMTP response rejected');
}

function smtpWrite($socket, string $command): void
{
    $bytes = $command . "\r\n";
    while ($bytes !== '') {
        $written = fwrite($socket, $bytes);
        if (!$written) throw new RuntimeException('SMTP write failed');
        $bytes = substr($bytes, $written);
    }
}

function sendSmtpMail(#[SensitiveParameter] array $config, string $recipient, string $subject, string $body): void
{
    foreach ([$recipient, $config['from']] as $email) if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) throw new RuntimeException('Invalid mail address');
    if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $config['host']) || !in_array($config['security'], ['none', 'tls', 'starttls'], true) || (int) $config['port'] < 1 || (int) $config['port'] > 65535) throw new RuntimeException('Invalid SMTP settings');
    if ($config['security'] === 'none' && !in_array($config['host'], ['localhost', '127.0.0.1'], true)) throw new RuntimeException('TLS required for remote SMTP');
    $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $config['host']]]);
    $socket = @stream_socket_client(($config['security'] === 'tls' ? 'tls' : 'tcp') . '://' . $config['host'] . ':' . (int) $config['port'], $code, $error, 5, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) throw new RuntimeException('SMTP unavailable');
    stream_set_timeout($socket, 5);
    try {
        smtpRead($socket, [220]); smtpWrite($socket, 'EHLO coffee.local'); smtpRead($socket, [250]);
        if ($config['security'] === 'starttls') {
            smtpWrite($socket, 'STARTTLS'); smtpRead($socket, [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('SMTP TLS failed');
            smtpWrite($socket, 'EHLO coffee.local'); smtpRead($socket, [250]);
        }
        if ($config['username'] !== '') {
            smtpWrite($socket, 'AUTH LOGIN'); smtpRead($socket, [334]);
            smtpWrite($socket, base64_encode($config['username'])); smtpRead($socket, [334]);
            smtpWrite($socket, base64_encode($config['password'])); smtpRead($socket, [235]);
        }
        smtpWrite($socket, 'MAIL FROM:<' . $config['from'] . '>'); smtpRead($socket, [250]);
        smtpWrite($socket, 'RCPT TO:<' . $recipient . '>'); smtpRead($socket, [250, 251]);
        smtpWrite($socket, 'DATA'); smtpRead($socket, [354]);
        $message = 'From: <' . $config['from'] . ">\r\nTo: <" . $recipient . ">\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\nDate: " . date(DATE_RFC2822)
            . "\r\nMessage-ID: <" . bin2hex(random_bytes(16)) . "@coffee.local>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
        smtpWrite($socket, $message . '.'); smtpRead($socket, [250]);
        smtpWrite($socket, 'QUIT');
    } finally { fclose($socket); }
}

function processOrderMail(): int
{
    $jobs = pendingOrderMail(); $sent = 0;
    foreach ($jobs as $job) {
        $order = findOrder((int) $job['order_id']);
        if (!$order || $order['anonymized']) { discardOrderMail((int) $job['id']); continue; }
        try {
            $config = smtpSettings();
            $recipient = $job['recipient_kind'] === 'manager' ? $config['manager'] : $order['customer']['email'];
            $body = 'Заказ ' . $order['number'] . ' получен. Сумма: ' . rubles($order['total']) . ".\nМенеджер подтвердит состав и получение. Онлайн-оплаты нет.\n";
            foreach ($order['items'] as $item) $body .= $item['name'] . ' × ' . $item['quantity'] . "\n";
            sendSmtpMail($config, $recipient, 'Заказ ' . $order['number'] . ' — Вкусный повод', $body);
            completeOrderMail((int) $job['id']); $sent++;
        } catch (Throwable $error) {
            $attempts = (int) $job['attempts'] + 1;
            failOrderMail((int) $job['id'], $attempts);
        }
    }
    return $sent;
}
