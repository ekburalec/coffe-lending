<?php
declare(strict_types=1);

return [
    'minimum_length' => 15,
    'maximum_length' => 128,
    'maximum_bytes' => 512,
    'argon_options' => ['memory_cost' => 19456, 'time_cost' => 2, 'threads' => 1],
    'check_pwned' => getenv('APP_PASSWORD_HIBP') === '1',
    // Небольшой локальный список полных слабых значений; это не база всех утечек.
    'blocklist' => [
        'password', 'passwordpassword', 'password1234567', 'password12345678',
        'password123456789', 'password1234567890', '123456789012345',
        '1234567890123456', '12345678901234567890', 'qwertyuiopasdfgh',
        'qwertyuiop123456', 'qwertyuiopasdfghjkl', 'adminadminadmin',
        'administrator123', 'administrator12345', 'letmeinletmeinletmein',
        'changemechangeme', 'iloveyouiloveyou', 'correcthorsebatterystaple',
        'парольпарольпароль', 'пароль1234567890', 'вкусныйповод12345',
    ],
];
