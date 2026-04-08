<?php $baseUrl = defined('URLROOT') ? URLROOT : '/cooking_website/public'; ?>

<div class="mx-auto w-full max-w-5xl">
    <div class="grid min-h-[70vh] grid-cols-1 overflow-hidden rounded-2xl bg-white shadow-xl lg:grid-cols-2">
        <div class="relative hidden lg:block">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900/80 to-slate-700/60"></div>
            <div class="h-full w-full bg-cover bg-center" style="background-image:url('https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=1200&q=80');"></div>
            <div class="absolute bottom-10 left-10 right-10 text-white">
                <h2 class="text-4xl font-bold">Khu vực quản trị</h2>
                <p class="mt-3 text-white/90">Dành riêng cho quản trị viên.</p>
            </div>
        </div>

        <div class="p-8 sm:p-12">
            <div class="mx-auto w-full max-w-md">
                <h1 class="text-3xl font-bold text-slate-900">Đăng nhập quản trị</h1>
                <p class="mt-2 text-slate-500">Vui lĂ²ng xĂ¡c thực tĂ i khoản admin.</p>

                <?php if (!empty($error)): ?>
                    <p class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <form method="post" action="<?= $baseUrl; ?>/admin/login" class="mt-6 space-y-5">
                    <?= csrf_field(); ?>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                        <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" type="email" name="email" autocomplete="email" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Mật khẩu</label>
                        <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" type="password" name="password" autocomplete="current-password" required>
                    </div>

                    <button class="w-full rounded-lg bg-slate-900 px-4 py-3 font-bold text-white transition hover:bg-slate-800" type="submit">Đăng nhập</button>
                </form>

                <p class="mt-6 text-sm text-slate-600">
                    Bạn là người dùng thường?
                    <a class="font-semibold text-amber-600 hover:underline" href="<?= $baseUrl; ?>/login">Đăng nhập tại đây</a>
                </p>
            </div>
        </div>
    </div>
</div>