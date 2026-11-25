# Счётчики символов SEO

assets/js/modules/admin-character-count.js экспортирует initAdminCharacterCount,
вызывается из admin.js. Поля с data-character-field содержат input/textarea и
счётчик data-character-count. SSR выводит mb_strlen, JS обновляет Array.from(value).length.
Ввод не меняется и не обрезается, live-регион не создаётся. Подсказка связана с полем
через aria-describedby; без JS остаётся исходная длина. Числа в подсказках — ориентиры,
серверные максимумы и валидация задаются отдельно в config/admin.php.
