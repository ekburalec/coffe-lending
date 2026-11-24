# Авторизация администратора

После `php bin/migrate.php` создать пользователя: пароль 12–72 байта передать через
stdin в `php bin/create-admin.php LOGIN`. Пароля по умолчанию нет. --reset явно
сбрасывает пароль и удаляет прежние сессии. Не сохранять пароль в репозитории.
/admin/login — GET/POST, /admin/logout — только POST с CSRF. Сессия HttpOnly,
SameSite=Strict, Secure при HTTPS, простаивает максимум 30 минут. ID и CSRF меняются
при входе. Пять неудач за 15 минут блокируют IP на остаток окна, proxy-заголовки не
используются. Все страницы админки no-store/noindex. Проверки: tests/integration.py.
