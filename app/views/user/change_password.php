<h1>Đổi mật khẩu</h1>

<?php if (!empty($error)): ?>
    <p class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<?php if (!empty($message)): ?>
    <p class="alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<form method="post" action="<?= URLROOT; ?>/profile/change-password" class="form">
    <?= csrf_field(); ?>
    <label>Mật khẩu mới</label>
    <input type="password" name="new_password" minlength="6" required>
    <button type="submit">Cập nhật mật khẩu</button>
</form>
