<form class="admin__form" method="post" action="/admin/login">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <p id="login-error" class="admin__message" role="alert"><?= e($error) ?></p>
    <label class="admin__field" for="username">Логин<input class="admin__input" id="username" name="username" autocomplete="username" required maxlength="80" aria-describedby="login-error"></label>
    <label class="admin__field" for="password">Пароль<input class="admin__input" type="password" id="password" name="password" autocomplete="current-password" required maxlength="1024" aria-describedby="login-error"></label>
    <button class="admin__button" type="submit">Войти</button>
</form>
