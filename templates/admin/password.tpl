<p class="admin__lead">Укажите текущий пароль. Новый можно ввести вручную или сгенерировать. После сохранения остальные сеансы потребуют нового входа.</p>
<?php if ($changed): ?><p class="admin__success" role="status">Пароль изменён.</p><?php endif; ?>
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
        <input class="admin__input password-control__input" id="new-password" name="new_password" type="password" autocomplete="new-password" required maxlength="72" aria-invalid="<?= isset($errors['new_password']) ? 'true' : 'false' ?>" aria-describedby="new-password-hint new-password-error">
        <?php render('admin/password-toggle', ['id' => 'new-password']); ?></div>
        <span class="admin__hint" id="new-password-hint">От 12 до 72 байт. Для латинских букв, цифр и знаков это 12–72 символа; кириллица занимает больше одного байта.</span>
        <span class="admin__message" id="new-password-error"><?= e($errors['new_password'] ?? '') ?></span>
        <div class="password-control__actions" data-password-generator data-password-target="new-password" data-password-confirmation="confirm-password" hidden><button class="admin__button admin__button--quiet" type="button" data-password-generate>Сгенерировать пароль</button><button class="admin__button admin__button--quiet" type="button" data-password-copy disabled>Скопировать</button></div>
        <p class="admin__hint" data-password-feedback role="status" aria-live="polite"></p>
    </div>
    <div class="admin__field">
        <label for="confirm-password">Повторите новый пароль</label>
        <div class="password-control" data-password-control>
        <input class="admin__input password-control__input" id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" required maxlength="72" aria-invalid="<?= isset($errors['confirm_password']) ? 'true' : 'false' ?>" aria-describedby="confirm-password-error">
        <?php render('admin/password-toggle', ['id' => 'confirm-password']); ?></div>
        <span class="admin__message" id="confirm-password-error"><?= e($errors['confirm_password'] ?? '') ?></span>
    </div>
    <button class="admin__button" type="submit">Изменить пароль</button>
</form>
