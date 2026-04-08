<?php $baseUrl = defined('URLROOT') ? URLROOT : '/cooking_website/public'; ?>

<div class="mx-auto w-full max-w-6xl">
    <div class="grid min-h-[70vh] grid-cols-1 overflow-hidden rounded-2xl bg-white shadow-xl lg:grid-cols-2">
        <div class="relative hidden lg:block">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-300/40 to-orange-500/20"></div>
            <div class="h-full w-full bg-cover bg-center" style="background-image:url('https://lh3.googleusercontent.com/aida-public/AB6AXuAJaBkK8j-D-ZY2seFIcVWO7s32dOxxBpqlVAGHQqCE3rdqK8WQXK-7wmOmdnffl2e_c_PVs97p0goXh-j0ML4N-Wo9IHdPnks-Tjuk6m3oJ0S6S8p_oUIZpFOoxQKDH-fgWBEWQIUPibrxOM-8zvnWHUJV_oDFQ2bsEfJMG67XjW7ecXWtdlek8pKtTcDCzlLrrJ3E_j94cLuyayujABGP6Pfy-ixKzKYcSjLBsBN9eyTF4NDV-I6uszNud1DQRdxAjTYmZQn_K7NB');"></div>
            <div class="absolute bottom-10 left-10 right-10 text-white">
                <h2 class="text-4xl font-bold">Bắt đầu hành trình ẩm thực</h2>
                <p class="mt-3 text-white/90">Tạo tài khoản để đăng công thức, bình luận và theo dõi đầu bếp bạn thích.</p>
            </div>
        </div>

        <div class="p-8 sm:p-12">
            <div class="mx-auto w-full max-w-md">
                <h1 class="text-3xl font-bold text-slate-900">Đăng ký</h1>
                <p class="mt-2 text-slate-500">Tạo tài khoản mới trong vài giây.</p>

                <?php if (!empty($error)): ?>
                    <p class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <form method="post" action="<?= $baseUrl; ?>/register" class="mt-6 space-y-5">
                    <?= csrf_field(); ?>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tên đăng nhập</label>
                        <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" type="text" name="username" value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                        <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Mật khẩu</label>
                        <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" type="password" name="password" minlength="6" autocomplete="new-password" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Xác nhận mật khẩu</label>
                        <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" type="password" name="confirm_password" minlength="6" autocomplete="new-password" required>
                    </div>

                    <button class="w-full rounded-lg bg-amber-500 px-4 py-3 font-bold text-white transition hover:bg-amber-600" type="submit">Tạo tài khoản</button>
                </form>

                <p class="mt-6 text-sm text-slate-600">
                    Đã có tài khoản?
                    <a class="font-semibold text-amber-600 hover:underline" href="<?= $baseUrl; ?>/login">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</div>
