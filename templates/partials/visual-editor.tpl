<aside class="visual-editor" aria-label="Редактор сайта" data-visual-editor data-csrf="<?= e($csrf) ?>" data-state="<?= e(json_encode($visualState, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) ?>">
    <div class="visual-editor__toolbar">
        <button class="visual-editor__toggle" type="button" data-visual-mode-toggle aria-pressed="true" aria-expanded="true" aria-controls="visual-controls" title="Включить или выключить редактирование">Редактор сайта</button>
        <div class="visual-editor__tools visual-editor__navigation">
            <a class="visual-editor__toggle" href="/admin">Админка</a>
            <form method="post" action="/admin/logout">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <button class="visual-editor__toggle" type="submit">Выйти</button>
            </form>
        </div>
    </div>
    <div class="visual-editor__controls" id="visual-controls" data-visual-controls>
        <div class="visual-editor__tools" role="group" aria-label="Сохранение и оформление">
            <button class="visual-editor__toggle visual-editor__toggle--primary" type="button" data-visual-save disabled>Сохранить</button>
            <button class="visual-editor__toggle" type="button" data-visual-cancel disabled>Отменить</button>
            <button class="visual-editor__toggle" type="button" data-visual-format="bold" aria-label="Жирный (Ctrl+B)" disabled><strong>Ж</strong></button>
            <button class="visual-editor__toggle" type="button" data-visual-format="italic" aria-label="Курсив (Ctrl+I)" disabled><em>К</em></button>
            <span class="visual-editor__count" data-visual-count role="status">Нет изменений</span>
        </div>
        <div class="visual-editor__selection">
            <p class="visual-editor__hint" data-visual-selection>Нажмите на текст или фото на сайте, чтобы изменить их.</p>
            <a class="visual-editor__link" data-visual-managed-link hidden>Открыть раздел</a>
        </div>
    </div>
    <p class="visual-editor__message" data-visual-message role="status" aria-live="polite" hidden></p>
    <a class="visual-editor__link" href="/admin/login" target="_blank" rel="noopener noreferrer" data-visual-login hidden>Войти в админку<span class="sr-only"> (откроется в новой вкладке)</span></a>
    <noscript><p class="visual-editor__hint">Для редактирования на сайте включите JavaScript. Обычные формы доступны в <a class="visual-editor__link" href="/admin">админке</a>.</p></noscript>
    <dialog class="visual-editor__dialog" data-visual-photo aria-labelledby="visual-photo-title">
        <form class="visual-editor__photo-form" data-visual-photo-form>
            <h2 class="visual-editor__dialog-title" id="visual-photo-title">Фотография блока</h2>
            <p class="visual-editor__hint" data-visual-photo-label></p>
            <img class="visual-editor__photo-preview" data-visual-photo-preview src="<?= e($visualState['images']['image-0']['src']) ?>" srcset="<?= e($visualState['images']['image-0']['src'] . ' 1x, ' . str_replace('.webp', '@2x.webp', $visualState['images']['image-0']['src']) . ' 2x') ?>" width="720" height="540" alt="Предпросмотр выбранной фотографии">
            <label for="visual-photo-alt">Описание фотографии (alt)</label>
            <input class="visual-editor__input" id="visual-photo-alt" data-visual-photo-alt type="text" maxlength="300" autocomplete="off" required aria-describedby="visual-photo-help">
            <p class="visual-editor__hint" id="visual-photo-help">Опишите, что изображено. Текст помогает людям, которые не видят фотографию.</p>
            <label for="visual-photo-file">Заменить фотографию</label>
            <input class="visual-editor__input" id="visual-photo-file" data-visual-photo-file type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="visual-photo-file-help visual-photo-error">
            <p class="visual-editor__hint" id="visual-photo-file-help">JPG, PNG или WebP до 10 МБ. Кадрирование по центру; рекомендуем исходник от 1440×1080.</p>
            <p class="visual-editor__message is-error" id="visual-photo-error" data-visual-photo-error role="alert"></p>
            <div class="visual-editor__tools">
                <button class="visual-editor__toggle visual-editor__toggle--primary" type="submit">Применить к странице</button>
                <button class="visual-editor__toggle" type="button" data-visual-photo-close>Закрыть</button>
            </div>
        </form>
    </dialog>
    <dialog class="visual-editor__dialog" data-visual-leave aria-labelledby="visual-leave-title">
        <h2 class="visual-editor__dialog-title" id="visual-leave-title">Есть несохранённые изменения</h2>
        <p>Сохраните их перед переходом или отмените. Можно остаться на сайте.</p>
        <div class="visual-editor__tools">
            <button class="visual-editor__toggle visual-editor__toggle--primary" type="button" data-visual-leave-save>Сохранить и перейти</button>
            <button class="visual-editor__toggle" type="button" data-visual-leave-discard>Отменить и перейти</button>
            <button class="visual-editor__toggle" type="button" data-visual-leave-stay>Остаться</button>
        </div>
    </dialog>
</aside>
