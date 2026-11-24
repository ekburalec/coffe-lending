<?php
declare(strict_types=1);

function adminAccount(): ?array
{
    return database()->query('SELECT * FROM admins WHERE id = 1')->fetch() ?: null;
}

function loginBlocked(string $key): bool
{
    $query = database()->prepare('SELECT failures, first_attempt FROM login_attempts WHERE ip_hash = ?');
    $query->execute([$key]);
    $row = $query->fetch();
    return $row && $row['failures'] >= 5 && $row['first_attempt'] > time() - 900;
}

function recordLoginFailure(string $key): void
{
    $query = database()->prepare('INSERT INTO login_attempts(ip_hash, failures, first_attempt) VALUES(?, 1, ?)
        ON CONFLICT(ip_hash) DO UPDATE SET
        failures = CASE WHEN first_attempt < ? THEN 1 ELSE failures + 1 END,
        first_attempt = CASE WHEN first_attempt < ? THEN excluded.first_attempt ELSE first_attempt END');
    $query->execute([$key, time(), time() - 900, time() - 900]);
    database()->prepare('DELETE FROM login_attempts WHERE first_attempt < ?')->execute([time() - 86400]);
}

function clearLoginFailures(string $key): void
{
    database()->prepare('DELETE FROM login_attempts WHERE ip_hash = ?')->execute([$key]);
}
