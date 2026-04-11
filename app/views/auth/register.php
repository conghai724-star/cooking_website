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

                <div class="relative mt-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="bg-white px-2 text-slate-500">Hoặc tiếp tục với</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a href="<?= $baseUrl; ?>/auth/google" class="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-50">
                        <svg class="h-5 w-5" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Đăng nhập bằng Google
                    </a>
                </div>


                <p class="mt-6 text-sm text-slate-600">
                    Đã có tài khoản?
                    <a class="font-semibold text-amber-600 hover:underline" href="<?= $baseUrl; ?>/login">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</div>
