<?php $baseUrl = defined('URLROOT') ? URLROOT : '/cooking_website/public'; ?>

<div class="mx-auto w-full max-w-6xl">
    <div class="grid min-h-[70vh] grid-cols-1 overflow-hidden rounded-2xl bg-white shadow-xl lg:grid-cols-2">
        <div class="relative hidden lg:block">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-300/40 to-orange-500/20"></div>
            <div class="h-full w-full bg-cover bg-center" style="background-image:url('https://lh3.googleusercontent.com/aida-public/AB6AXuDnRVtccSi_tDG7pFyTKqp-AAdcoFit6gpb6cdoccqGfrbzy0le8nj96y-bcORsvZjnGOwWj2UK0va5Q3eIa3R-SR1nu2PUsUkGNG4jIVOMJiNiZZXbsEQJ4eC1ZS40O_kWQJk-exEkuYT_xoD4R0Wt5fHUEYWEYQ4DI8qjp7kR-MKIXTan4VtU7NjVlkbulRX1I7aF-hGn2ojnCdxl-Teq5pc8uSDdYGt_FagOtUHaAlPaSePgS_swqhmjT4PrSXvy7BL0fxj8PN6f');"></div>
            <div class="absolute bottom-10 left-10 right-10 text-white">
                <h2 class="text-4xl font-bold">Nấu ăn dễ hơn mỗi ngày</h2>
                <p class="mt-3 text-white/90">Lưu công thức, chia sẻ món ngon và khám phá ý tưởng mới.</p>
            </div>
        </div>

        <div class="p-8 sm:p-12">
            <div class="mx-auto w-full max-w-md">
                <h1 class="text-3xl font-bold text-slate-900">Đăng nhập</h1>
                <p class="mt-2 text-slate-500">Chào mừng bạn quay lại.</p>

                <?php if (!empty($success)): ?>
                    <p class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <p class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <form method="post" action="<?= $baseUrl; ?>/login" class="mt-6 space-y-5">
                    <?= csrf_field(); ?>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                        <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" type="email" name="email" autocomplete="email" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Mật khẩu</label>
                        <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200" type="password" name="password" autocomplete="current-password" required>
                        <div class="mt-2 text-right">
                            <a class="text-sm font-semibold text-amber-600 hover:underline" href="<?= $baseUrl; ?>/forgot-password">Quên mật khẩu?</a>
                        </div>
                    </div>

                    <button class="w-full rounded-lg bg-amber-500 px-4 py-3 font-bold text-white transition hover:bg-amber-600" type="submit">Đăng nhập</button>
                </form>

                <p class="mt-6 text-sm text-slate-600">
                    Bạn chưa có tài khoản?
                    <a class="font-semibold text-amber-600 hover:underline" href="<?= $baseUrl; ?>/register">Đăng ký ngay</a>
                </p>
            </div>
        </div>
    </div>
</div>
