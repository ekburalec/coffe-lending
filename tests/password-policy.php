<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/services/passwords.php';
require APP_ROOT . '/app/services/pwned-passwords.php';
putenv('APP_PASSWORD_HIBP=0');

function expectPassword(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

expectPassword(newPasswordError('я' . str_repeat('x', 13), 'qatest') !== null, 'Count characters, not bytes.');
expectPassword(newPasswordError(str_repeat('я', 15), 'qatest') === null, 'Allow 15 Unicode characters without composition rules.');
expectPassword(newPasswordError(' long phrase with spaces ', 'qatest') === null, 'Allow spaces.');
expectPassword(newPasswordError('passwordpassword', 'qatest') !== null, 'Reject full blocklisted value.');
expectPassword(newPasswordError('PasswordPassword', 'qatest') !== null, 'Blocklist case folding.');
expectPassword(newPasswordError('manager1234567890', 'manager') !== null, 'Block expected login derivative.');
expectPassword(newPasswordError('xx-passwordpassword-xx', 'qatest') === null, 'Compare whole values, not substrings.');
foreach ([null, [], str_repeat('x', 129), str_repeat('я', 129), str_repeat('x', 15) . "\0", "\xff" . str_repeat('x', 15)] as $invalid) {
    expectPassword(newPasswordError($invalid, 'qatest') !== null, 'Reject malformed or oversized password.');
}
$legacyPassword = 'legacy-pass1';
$legacyHash = password_hash($legacyPassword, PASSWORD_BCRYPT);
expectPassword(verifyAdminPassword($legacyPassword, $legacyHash), 'Keep legacy short password sign-in.');
expectPassword(newPasswordError($legacyPassword, 'qatest') !== null, 'New minimum applies only on setting.');
$legacyLong = str_repeat('a', 72);
$legacyLongHash = password_hash($legacyLong, PASSWORD_BCRYPT);
expectPassword(verifyAdminPassword($legacyLong, $legacyLongHash), 'Bcrypt 72-byte boundary.');
expectPassword(!verifyAdminPassword($legacyLong . 'x', $legacyLongHash), 'Reject bcrypt truncation alias.');
expectPassword(!verifyAdminPassword([], $legacyLongHash), 'Reject arrays on sign-in.');
if (hasArgonPasswords()) {
    $long = str_repeat('😀', 128);
    expectPassword(newPasswordError($long, 'qatest') === null, '128 code points and 512 bytes accepted.');
    $hash = hashAdminPassword($long);
    expectPassword(password_get_info($hash)['algoName'] === 'argon2id', 'Use Argon2id.');
    expectPassword(verifyAdminPassword($long, $hash) && !verifyAdminPassword($long . 'x', $hash), 'Full Argon password verification.');
}
$spaced = ' phrase with spaces preserved ';
$hash = hashAdminPassword($spaced);
expectPassword(verifyAdminPassword($spaced, $hash) && !verifyAdminPassword(trim($spaced), $hash), 'Do not trim stored password.');
echo "PASS: Unicode/code-point lengths, byte caps, local blocklist, spaces, legacy bcrypt, truncation rejection and Argon2id.\n";

$sample = 'qa-only-pwned-test-value';
$digest = strtoupper(sha1($sample));
$suffix = substr($digest, 5);
expectPassword(pwnedPasswordError($sample, function (string $prefix) use ($digest, $suffix): string {
    expectPassword(strlen($prefix) === 5 && $prefix === substr($digest, 0, 5), 'Only five hash characters reach transport.');
    return str_repeat('0', 35) . ":17\r\n" . $suffix . ":0\r\n";
}) === null, 'Ignore padding and unrelated suffixes.');
expectPassword(pwnedPasswordError($sample, fn (): string => $suffix . ':2') !== null, 'Reject breached password.');
foreach (['', '<html>error</html>', $suffix . ':0' . "\ninvalid", str_repeat('x', 200001)] as $badResponse) {
    expectPassword(str_contains(pwnedPasswordError($sample, fn (): string => $badResponse) ?? '', 'недоступна'), 'Malformed response must not pass.');
}
expectPassword(str_contains(pwnedPasswordError($sample, function (): string { throw new RuntimeException('simulated timeout'); }) ?? '', 'недоступна'), 'Transport failure must not pass.');
expectPassword(newPasswordBreachError($sample) === null, 'Online lookup disabled by default.');
echo "PASS: HIBP prefix privacy, suffix matching, padding, malformed responses, transport failure and disabled default.\n";
