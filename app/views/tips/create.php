<?php
$error = (string) ($error ?? '');
$success = (bool) ($success ?? false);
?>

<div class="w-full">
    <div class="mx-auto w-full max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Góp ý mẹo vặt</h1>
            <p class="text-sm text-slate-500">Gửi mẹo vặt của bạn để admin duyệt và chia sẻ với cộng đồng.</p>
        </div>

        <?php if ($success): ?>
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">Đã gửi mẹo vặt. Vui lòng chờ duyệt.</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form class="space-y-4" method="post" enctype="multipart/form-data">
            <?= csrf_field(); ?>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Tiêu đề</label>
                <input class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="title" type="text" required>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Tóm tắt</label>
                <textarea class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="excerpt" rows="3" placeholder="Tóm tắt ngắn cho mẹo vặt..."></textarea>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Nội dung</label>
                <textarea class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary" name="content" rows="8" required></textarea>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Ảnh bìa</label>
                <input class="w-full text-sm" name="cover_image" type="file" accept="image/*">
            </div>
            <div class="flex items-center gap-3">
                <button class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white" type="submit">Gửi mẹo vặt</button>
                <a class="text-sm font-semibold text-slate-500 hover:text-slate-900" href="<?= URLROOT; ?>/tips">Quay lại</a>
            </div>
        </form>
    </div>
</div>
