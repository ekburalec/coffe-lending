<?php
declare(strict_types=1);

function passwordPolicy(): array
{
    static $policy;
    return $policy ??= require APP_ROOT . '/config/passwords.php';
}

function hasArgonPasswords(): bool
{
    return in_array('argon2id', password_algos(), true);
}

function newPasswordByteLimit(): int
{
    return hasArgonPasswords() ? passwordPolicy()['maximum_bytes'] : 72;
}

function newPasswordHint(): string
{
    $policy = passwordPolicy();
    return 'Требования к новому паролю: от ' . $policy['minimum_length'] . ' до ' . $policy['maximum_length']
        . ' символов, не более ' . newPasswordByteLimit() . ' байт. Пробелы сохраняются.';
}

function newPasswordError(#[SensitiveParameter] mixed $password, string $username): ?string
{
    $policy = passwordPolicy();
    if (!is_string($password) || strlen($password) > newPasswordByteLimit()
        || str_contains($password, "\0") || !mb_check_encoding($password, 'UTF-8')) {
        return newPasswordHint() . ' Нулевой символ и некорректный UTF-8 недопустимы.';
    }
    $length = mb_strlen($password, 'UTF-8');
    if ($length < $policy['minimum_length'] || $length > $policy['maximum_length']) return newPasswordHint();
    $blocked = $policy['blocklist'];
    foreach (['', '123', '12345', '123456', '123456789', '1234567890', 'password', 'admin'] as $suffix) $blocked[] = $username . $suffix;
    if (in_array(mb_strtolower($password, 'UTF-8'), array_map(fn (string $value): string => mb_strtolower($value, 'UTF-8'), $blocked), true)) {
        return 'Этот пароль слишком распространён или связан с логином. Выберите другой.';
    }
    return null;
}

function hashAdminPassword(#[SensitiveParameter] string $password): string
{
    return hasArgonPasswords()
        ? password_hash($password, PASSWORD_ARGON2ID, passwordPolicy()['argon_options'])
        : password_hash($password, PASSWORD_DEFAULT);
}

/** Проверка старых и новых хешей без применения политики установки к прежнему паролю. */
function verifyAdminPassword(#[SensitiveParameter] mixed $password, string $hash): bool
{
    $limit = password_get_info($hash)['algoName'] === 'bcrypt' ? 72 : passwordPolicy()['maximum_bytes'];
    return is_string($password) && strlen($password) <= $limit && !str_contains($password, "\0") && password_verify($password, $hash);
}
