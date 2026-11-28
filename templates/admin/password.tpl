<p class="admin__lead">Новый пароль действует сразу после сохранения. Пароль хранится только в виде хеша: посмотреть его нельзя, только задать новый.</p>
<div class="admin__row"><p class="admin__hint">Логин: <strong><?= e($username) ?></strong>. Здесь он не меняется. Для смены логина и сброса пароля выполните на сервере <code>php bin/create-admin.php НОВЫЙ_ЛОГИН --reset</code>, передав новый пароль через stdin.</p></div>
<p class="admin__lead">Укажите текущий пароль. Новый можно ввести вручную или сгенерировать. После сохранения остальные сеансы потребуют нового входа.</p>
<?php if ($changed): ?><p class="admin__success" role="status">Пароль изменён.</p><?php endif; ?>
<form id="password-generator-settings" hidden></form>
<form class="admin__form" method="post" action="/admin/password">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if (isset($errors['_form'])): ?><p class="admin__message" role="alert"><?= e($errors['_form']) ?></p><?php endif; ?>
    <div class="admin__field">
        <label for="current-password">Текущий пароль</label>
        <div class="password-control" data-password-control>
        <input class="admin__input password-control__input" id="current-password" name="current_password" type="password" autocomplete="current-password" required maxlength="1024" aria-invalid="<?= isset($errors['current_password']) ? 'true' : 'false' ?>" aria-describedby="current-password-error">
        <?php render('admin/password-toggle', ['id' => 'current-password']); ?></div>
        <span class="admin__message" id="current-password-error"><?= e($errors['current_password'] ?? '') ?></span>
    </div>
    <div class="admin__field">
        <label for="new-password">Новый пароль</label>
        <div class="password-control" data-password-control>
        <input class="admin__input password-control__input" id="new-password" name="new_password" type="password" autocomplete="new-password" required maxlength="<?= e($passwordMaximum * 2) ?>" aria-invalid="<?= isset($errors['new_password']) ? 'true' : 'false' ?>" aria-describedby="new-password-strength-status new-password-hint new-password-check-hint new-password-error" data-password-strength-input>
        <?php render('admin/password-toggle', ['id' => 'new-password']); ?></div>
        <div class="password-strength" data-password-strength hidden>
            <div class="password-strength__bar" role="meter" aria-label="Предварительная оценка надёжности нового пароля" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0" aria-valuetext="Введите пароль" data-password-strength-meter>
                <span class="password-strength__segment" aria-hidden="true" data-password-strength-segment></span>
                <span class="password-strength__segment" aria-hidden="true" data-password-strength-segment></span>
                <span class="password-strength__segment" aria-hidden="true" data-password-strength-segment></span>
                <span class="password-strength__segment" aria-hidden="true" data-password-strength-segment></span>
            </div>
            <p class="password-strength__status" id="new-password-strength-status" aria-live="polite" data-password-strength-status>Введите пароль</p>
        </div>
        <span class="admin__hint" id="new-password-hint"><?= e($passwordHint) ?></span>
        <span class="admin__hint" id="new-password-check-hint"><?= e($passwordBreachHint) ?></span>
        <span class="admin__message" id="new-password-error"><?= e($errors['new_password'] ?? '') ?></span>
        <fieldset class="password-control__generator" data-password-generator data-password-target="new-password" data-password-confirmation="confirm-password" data-password-max-bytes="<?= e($passwordBytes) ?>" hidden>
            <legend class="password-control__legend">Генератор пароля</legend>
            <div class="admin__field">
                <label for="password-mode">Какой пароль создать</label>
                <select class="admin__input" id="password-mode" form="password-generator-settings" data-password-mode>
                    <option value="characters">Случайные символы</option>
                    <option value="phrase">Парольная фраза</option>
                </select>
            </div>
            <div class="password-control__character-options" data-password-character-options>
                <div class="admin__field">
                    <label for="password-length">Длина генерируемого пароля</label>
                    <input class="admin__input" id="password-length" type="number" min="16" max="64" step="1" value="20" inputmode="numeric" autocomplete="off" form="password-generator-settings" aria-describedby="password-generator-hint password-generator-feedback" data-password-length>
                </div>
            <p class="admin__hint" id="password-generator-hint">Длина в генераторе: от 16 до 64 символов. Настройки применятся при следующей генерации.</p>
            <details class="password-control__advanced">
                <summary class="password-control__summary">Дополнительные настройки</summary>
                <fieldset class="password-control__groups">
                    <legend class="password-control__legend">Разрешённые символы</legend>
                    <div class="password-control__option"><input class="password-control__checkbox" id="password-uppercase" type="checkbox" checked form="password-generator-settings" data-password-group="uppercase"><label for="password-uppercase">Заглавные A–Z</label></div>
                    <div class="password-control__option"><input class="password-control__checkbox" id="password-lowercase" type="checkbox" checked form="password-generator-settings" data-password-group="lowercase"><label for="password-lowercase">Строчные a–z</label></div>
                    <div class="password-control__option"><input class="password-control__checkbox" id="password-digits" type="checkbox" checked form="password-generator-settings" data-password-group="digits"><label for="password-digits">Цифры 0–9</label></div>
                    <div class="password-control__option"><input class="password-control__checkbox" id="password-symbols" type="checkbox" checked form="password-generator-settings" data-password-group="symbols"><label for="password-symbols">Спецсимволы</label></div>
                </fieldset>
                <p class="admin__hint">Включённая группа разрешает её символы. Они могут отсутствовать в отдельном пароле.</p>
                <div class="admin__field">
                    <label for="password-custom-symbols">Набор спецсимволов</label>
                    <input class="admin__input" id="password-custom-symbols" type="text" value="-_" maxlength="128" autocomplete="off" autocapitalize="off" spellcheck="false" form="password-generator-settings" aria-describedby="password-symbols-hint password-generator-feedback" data-password-custom-symbols>
                    <span class="admin__hint" id="password-symbols-hint">Знаки с английской раскладки, например !, -, _. Без букв, цифр и пробелов.</span>
                </div>
                <div class="admin__field">
                    <label for="password-excluded-chars">Дополнительно исключить символы</label>
                    <input class="admin__input" id="password-excluded-chars" type="text" maxlength="128" autocomplete="off" autocapitalize="off" spellcheck="false" form="password-generator-settings" aria-describedby="password-excluded-hint password-generator-feedback" data-password-excluded-chars>
                    <span class="admin__hint" id="password-excluded-hint">Укажите подряд все нежелательные символы. Повторения не учитываются.</span>
                </div>
                <div class="password-control__option">
                    <input class="password-control__checkbox" id="password-require-groups" type="checkbox" form="password-generator-settings" data-password-require-groups>
                    <label for="password-require-groups">Хотя бы один символ каждой выбранной группы</label>
                </div>
            </details>
            </div>
            <div class="admin__field" data-password-phrase-options hidden>
                <label for="password-word-count">Количество слов</label>
                <input class="admin__input" id="password-word-count" type="number" min="6" max="8" step="1" value="7" inputmode="numeric" autocomplete="off" form="password-generator-settings" aria-describedby="password-phrase-hint password-generator-feedback" data-password-word-count>
                <span class="admin__hint" id="password-phrase-hint">Случайные английские слова через пробел. Фильтры отдельных символов к фразе не применяются. Настройки применятся при следующей генерации.</span>
            </div>
            <div class="password-control__actions"><button class="admin__button admin__button--quiet" type="button" data-password-generate>Сгенерировать пароль</button><button class="admin__button admin__button--quiet" type="button" data-password-copy disabled>Скопировать</button></div>
        </fieldset>
        <p class="admin__hint" id="password-generator-feedback" data-password-feedback role="status" aria-live="polite"></p>
    </div>
    <div class="admin__field">
        <label for="confirm-password">Повторите новый пароль</label>
        <div class="password-control" data-password-control>
        <input class="admin__input password-control__input" id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" required maxlength="<?= e($passwordMaximum * 2) ?>" aria-invalid="<?= isset($errors['confirm_password']) ? 'true' : 'false' ?>" aria-describedby="confirm-password-error">
        <?php render('admin/password-toggle', ['id' => 'confirm-password']); ?></div>
        <span class="admin__message" id="confirm-password-error"><?= e($errors['confirm_password'] ?? '') ?></span>
    </div>
    <button class="admin__button" type="submit">Изменить пароль</button>
</form>
