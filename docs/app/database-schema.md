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
