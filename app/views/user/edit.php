<?php
$user = is_array($user ?? null) ? $user : [];
$name = (string) ($user['name'] ?? '');
$email = (string) ($user['email'] ?? '');
$bio = (string) ($user['bio'] ?? '');
$avatar = trim((string) ($user['avatar'] ?? ''));
$avatarUrl = $avatar !== '' ? URLROOT . '/uploads/' . rawurlencode($avatar) : '';
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-4xl rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <h1 class="text-2xl font-bold text-slate-900">Sua hồ sơ</h1>
        <p class="mt-1 text-sm text-slate-500">Cap nhat thong tin ca nhan cua ban.</p>

        <?php if (!empty($message ?? '')): ?>
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                <?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error ?? '')): ?>
            <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="mt-5 border-b border-slate-200">
            <nav class="-mb-px flex flex-wrap gap-2 sm:gap-6" aria-label="Tabs">
                <button
                    type="button"
                    class="profile-tab inline-flex items-center border-b-2 px-1 py-3 text-sm font-semibold transition"
                    data-tab="info"
                >
                    Thong tin
                </button>
                <button
                    type="button"
                    class="profile-tab inline-flex items-center border-b-2 px-1 py-3 text-sm font-semibold transition"
                    data-tab="password"
                >
                    Doi mật khẩu
                </button>
                <button
                    type="button"
                    class="profile-tab inline-flex items-center border-b-2 px-1 py-3 text-sm font-semibold transition"
                    data-tab="email"
                >
                    Doi email
                </button>
            </nav>
        </div>

        <form class="mt-5 space-y-5" method="post" action="<?= URLROOT; ?>/profile/edit" enctype="multipart/form-data">
            <?= csrf_field(); ?>
            <section class="space-y-4" data-tab-panel="info">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="name">Ten</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" id="name" name="name" type="text" maxlength="100" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="bio">Gioi thieu</label>
                    <textarea class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" id="bio" name="bio" rows="4" maxlength="500"><?= htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <p class="mt-1 text-xs text-slate-400">Tối đa 500 ký tự.</p>
                </div>

                <div>
                    <p class="mb-2 block text-sm font-semibold text-slate-700">Anh dai dien</p>
                    <div class="flex items-center gap-3">
                        <?php if ($avatarUrl !== ''): ?>
                            <img class="h-14 w-14 rounded-full object-cover" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Anh dai dien hien tai">
                        <?php else: ?>
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-700">
                                <?= htmlspecialchars(strtoupper(substr($name !== '' ? $name : 'U', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        <input class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,.gif">
                    </div>
                    <?php if ($avatarUrl !== ''): ?>
                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600">
                            <input class="rounded border-slate-300" type="checkbox" name="remove_avatar" value="1">
                            Xoa anh dai dien hien tai
                        </label>
                    <?php endif; ?>
                </div>
            </section>

            <section class="space-y-4 hidden" data-tab-panel="password">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="current_password">Mật khẩu hien tai (bat buoc khi đổi email hoac doi mật khẩu)</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" id="current_password" name="current_password" type="password" autocomplete="current-password">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="new_password">Mật khẩu moi</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" id="new_password" name="new_password" type="password" minlength="6" autocomplete="new-password">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="confirm_new_password">Xác nhận mật khẩu moi</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" id="confirm_new_password" name="confirm_new_password" type="password" minlength="6" autocomplete="new-password">
                </div>
            </section>

            <section class="space-y-4 hidden" data-tab-panel="email">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="email">Email</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" id="email" name="email" type="email" maxlength="255" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                    <p class="mt-1 text-xs text-slate-400">Nhap email moi, sau do xac nhan bang mật khẩu hien tai ben duoi.</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700" for="email_current_password">Mật khẩu hien tai (xac nhan đổi email)</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" id="email_current_password" name="email_current_password" type="password" autocomplete="current-password">
                </div>
            </section>

            <div class="flex items-center gap-3 pt-2">
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-amber-500" type="submit">Luu thay doi</button>
                <a class="text-sm font-semibold text-slate-500 hover:text-slate-900" href="<?= URLROOT; ?>/profile">Huy</a>
            </div>
        </form>
    </div>
</section>

<script>
(function () {
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.profile-tab'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('[data-tab-panel]'));

    if (!tabs.length || !panels.length) {
        return;
    }

    function setActive(tabName) {
        tabs.forEach(function (tab) {
            var active = tab.getAttribute('data-tab') === tabName;
            tab.classList.toggle('border-amber-500', active);
            tab.classList.toggle('text-amber-600', active);
            tab.classList.toggle('border-transparent', !active);
            tab.classList.toggle('text-slate-500', !active);
        });

        panels.forEach(function (panel) {
            var active = panel.getAttribute('data-tab-panel') === tabName;
            panel.classList.toggle('hidden', !active);
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            setActive(tab.getAttribute('data-tab'));
        });
    });

    setActive('info');
})();
</script>
