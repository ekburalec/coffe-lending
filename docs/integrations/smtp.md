# SMTP и уведомления заказов

Режим разработки: Mailpit, localhost:1025, без TLS и авторизации, тестовые адреса.
Наличие процесса проверяется отдельно; приложение не устанавливает Mailpit молча.
Настройки хранятся в APP_DATA_DIR/smtp.json с правами 0600, не в БД/архивах/dist.
Пресеты Яндекс/Gmail/свой SMTP; пароль не возвращается браузеру.
Администратор может проверить отправку. На хостинге нужны разрешённый исходящий
SMTP, действительный отправитель и cron каждую минуту:

    php /path/to/bin/send-order-mail.php

Отправка вне HTTP-запроса из транзакционной очереди, ограниченные повторы.
Протокол SMTP с TLS проверяет сертификат; AUTH только по TLS или на loopback.
Письмо менеджеру и клиенту при непустом e-mail. В письме нет полного адреса,
контакты менеджер смотрит в защищённой админке. Ошибки без тел писем и паролей.
За очистку доставленных сообщений в почтовом ящике отвечает владелец.


Транспорт: небольшой SMTP-клиент на PHP streams/OpenSSL, без composer и внешних
пакетов; поддерживает TLS, STARTTLS, AUTH LOGIN и текстовый MIME UTF-8.
Задания отправляются под application.lock; второй worker не отправит те же задания
параллельно. После неопределённого сбоя SMTP возможна повторная доставка письма,
поэтому обработка заказа опирается на номер в БД.

Проверка 28.11.2026: официальный Mailpit v1.34.0 установлен только в var/tools,
локальная отправка и приём через API подтверждены. Запуск для разработки:

    .\var\tools\mailpit\mailpit.exe --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025 --database var/tools/mailpit-test.sqlite --disable-version-check

Источники параметров пресетов:
- [Яндекс](https://www.yandex.ru/support/yandex-360/customers/mail/ru/mail-clients/mail-clients-troubleshooting).
- [Gmail](https://developers.google.com/workspace/gmail/imap/imap-smtp).
- [Mailpit](https://github.com/axllent/mailpit/releases/tag/v1.34.0).
