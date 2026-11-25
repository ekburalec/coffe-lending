# Сборка PHP-дистрибутива

Точка входа bin/build-dist.php вызывает buildPhpDist из bin/build-php.php.
Существующие функции объединения CSS/JS и проверки ассетов переиспользованы.
Изолированный импорт seed.json проверяет исходную страницу без чтения рабочей БД.
В dist включены app/templates, разрешённые config, index/router, CLI migrate,
create-admin/doctor, объединённые CSS/JS с хешами, используемые исходные картинки,
иконки, шрифты, лицензии и файлы deploy. accounts/var/загрузок в дистрибутиве нет.
config/release.php переключает стандартный путь данных на соседний coffee-data;
APP_DATA_DIR и APP_UPLOAD_DIR имеют приоритет. config/assets.php хранит версии CSS/JS.
Живые SEO/ИИ-маршруты обрабатываются PHP, а не сохраняются как устаревающие файлы.
Порядок установки и обновления: ../specs/deploy.md.
