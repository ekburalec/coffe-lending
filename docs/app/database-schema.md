# SQLite: схема v1

`schema_version` — версия миграции; импорт только на пустой базе.
`records` — типизированные коллекции: составной ключ kind/id, JSON payload,
целый sort_order, visible 0/1. Типы: categories, products, navigation, hours,
exceptions, content, images, faq, announcements, reviews. Связь products.category
проверяется серверным валидатором; удаление занятой категории запрещено.
`settings` — именованные группы contacts, delivery, seo, rating с JSON payload.
`admins` — единственная запись id=1, username и password_hash.
`login_attempts` — хеш IP, число ошибок, начало окна ограничения.

Доступ только через PDO prepared statements. JSON не является HTML.
Миграция: `php bin/migrate.php`; seed.json — исходная демо-редакция, не рабочая БД.
Конкурентные записи сериализует SQLite, busy_timeout 5 секунд. Файлы в APP_DATA_DIR.

При повторной миграции отсутствующие фиксированные поля content/images/hours
добавляются из seed без перезаписи имеющихся значений. Каталог и настройки не
пересеиваются; это позволяет обновлять шаблон с новыми фиксированными полями.

Миграция данных 2 однократно добавляет/показывает пункт «Отзывы» → #reviews.
Физическая схема остаётся v1, schema_version хранит журнал [1, 2]. Повторные
запуски не возвращают удалённую ссылку и не отменяют последующее редактирование.
Копии со старым журналом [1] остаются допустимыми; после обновления запускайте migrate.

settings.seo дополнен необязательными schema_description и image_alt. Пустые или
отсутствующие значения используют description/og_title. Новых таблиц нет.
