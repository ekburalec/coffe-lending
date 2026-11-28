<?php
declare(strict_types=1);

/** Передаёт стороннему сервису только пятизначный префикс, без пароля/полного хеша. */
function fetchPwnedRange(string $prefix): string
{
    if (!preg_match('/\A[A-F0-9]{5}\z/', $prefix)) throw new InvalidArgumentException('Некорректный префикс.');
    $context = stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 4, 'follow_location' => 0,
            'header' => "Add-Padding: true\r\nUser-Agent: CoffeeAdminPasswordCheck/1.0\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    set_error_handler(function (): never { throw new RuntimeException('Сервис проверки недоступен.'); });
    try {
        $body = file_get_contents('https://api.pwnedpasswords.com/range/' . $prefix, false, $context, 0, 200001);
    } finally {
        restore_error_handler();
    }
    if (!is_string($body) || strlen($body) > 200000 || !preg_match('~^HTTP/\S+ 200\b~', $http_response_header[0] ?? '')) {
        throw new RuntimeException('Некорректный ответ сервиса проверки.');
    }
    return $body;
}

function pwnedSuffixFound(string $body, string $suffix): bool
{
    $valid = false;
    $found = false;
    if (strlen($body) > 200000) throw new RuntimeException('Слишком большой ответ сервиса проверки.');
    foreach (preg_split('/\r?\n/', $body) as $line) {
        if ($line === '') continue;
        if (!preg_match('/\A([A-F0-9]{35}):([0-9]+)\z/i', $line, $match)) throw new RuntimeException('Некорректный ответ сервиса проверки.');
        $valid = true;
        if (hash_equals($suffix, strtoupper($match[1])) && ltrim($match[2], '0') !== '') $found = true;
    }
    if (!$valid) throw new RuntimeException('Пустой ответ сервиса проверки.');
    return $found;
}

/** Транспорт подменяется только в изолированных тестах; HTTP-ввод его не задаёт. */
function pwnedPasswordError(#[SensitiveParameter] string $password, ?callable $transport = null): ?string
{
    $hash = strtoupper(sha1($password));
    try {
        $body = ($transport ?? 'fetchPwnedRange')(substr($hash, 0, 5));
        if (pwnedSuffixFound($body, substr($hash, 5))) return 'Этот пароль найден в известных утечках. Выберите другой.';
        return null;
    } catch (Throwable) {
        return 'Проверка утечек временно недоступна. Пароль не изменён. Повторите позже.';
    }
}

function newPasswordBreachError(#[SensitiveParameter] string $password): ?string
{
    return passwordPolicy()['check_pwned'] ? pwnedPasswordError($password) : null;
}
