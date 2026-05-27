<?php require_once __DIR__ . '/helpers.php'; ?>
<h1><?= e(t('admin_login')) ?></h1>
<form method="post" action="/admin/login">
    <div class="form-group">
        <label for="password"><?= e(t('password')) ?></label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="btn"><?= e(t('login')) ?></button>
</form>
