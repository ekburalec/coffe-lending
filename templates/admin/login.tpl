<?php if (isset($_GET['restored'])): ?><p class="admin__success">Копия восстановлена. Войдите с учётной записью из копии.</p><?php endif; ?>
<form class="admin__form" method="post" action="/admin/login">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <p id="login-error" class="admin__message" role="alert"><?= e($error) ?></p>
    <label class="admin__field" for="username">Логин<input class="admin__input" id="username" name="username" autocomplete="username" required maxlength="80" aria-describedby="login-error"></label>
    <div class="admin__field"><label for="password">Пароль</label><div class="password-control" data-password-control><input class="admin__input password-control__input" type="password" id="password" name="password" autocomplete="current-password" required maxlength="1024" aria-describedby="login-error"><?php render('admin/password-toggle', ['id' => 'password']); ?></div></div>
    <button class="admin__button" type="submit">Войти</button>
</form>
