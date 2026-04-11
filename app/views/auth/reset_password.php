<?php
$baseUrl = defined('URLROOT') ? URLROOT : '/cooking_website/public';
$token = htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8');
$showForm = isset($showForm) ? (bool) $showForm : false;
?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid min-h-[70vh] grid-cols-1 overflow-hidden rounded-2xl bg-white shadow-xl lg:grid-cols-2">
        <div class="relative hidden lg:block">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-300/40 to-orange-500/20"></div>
            <div class="h-full w-full bg-cover bg-center" style="background-image:url('https://images.unsplash.com/photo-1514516870923-2a0fc0e6e29e?auto=format&fit=crop&w=1200&q=80');"></div>
            <div class="absolute bottom-10 left-10 right-10 text-white">
                <h2 class="text-4xl font-bold">Đặt lại mật khẩu</h2>
                <p class="mt-3 text-white/90">Nhập mật khẩu mới để truy cập lại tài khoản của bạn.</p>
            </div>
        </div>

        <div class="p-8 sm:p-12">
            <div class="mx-auto w-full max-w-md">
                <h1 class="text-3xl font-bold text-slate-900">Đặt lại mật khẩu</h1>
                <p class="mt-2 text-slate-500">Nhập mật khẩu mới của bạn bên dưới.</p>

                <?php if (!empty($success ?? '')): ?>
                    <p class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if (!empty($error ?? '')): ?>
                    <p class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if ($showForm): ?>
                    <form method="post" action="<?= $baseUrl; ?>/reset-password" class="mt-6 space-y-5">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="token" value="<?= $token; ?>">

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700" for="new_password">Mật khẩu mới</label>
                            <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" id="new_password" name="new_password" type="password" minlength="6" autocomplete="new-password" required>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700" for="confirm_new_password">Xác nhận mật khẩu mới</label>
                            <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" id="confirm_new_password" name="confirm_new_password" type="password" minlength="6" autocomplete="new-password" required>
                        </div>

                        <button class="w-full rounded-lg bg-amber-500 px-4 py-3 font-bold text-white transition hover:bg-amber-600" type="submit">Đặt lại mật khẩu</button>
                    </form>
                <?php else: ?>
                    <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-700">
                        <?php if (empty($success ?? '')): ?>
                            Vui lòng kiểm tra lại liên kết đặt lại mật khẩu hoặc thử lại từ đầu.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <p class="mt-6 text-sm text-slate-600">
                    Đã nhớ mật khẩu?
                    <a class="font-semibold text-amber-600 hover:underline" href="<?= $baseUrl; ?>/login">Quay lại Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</div>
