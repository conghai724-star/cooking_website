<?php $error = (string) ($error ?? ''); ?>

<div class="w-full">
    <div class="mx-auto max-w-3xl">
        <h1 class="mb-6 text-4xl font-black text-slate-900">Đăng câu hỏi</h1>

        <?php if ($error !== ''): ?>
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
            <?= csrf_field(); ?>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Tiêu đề</label>
                <input type="text" name="title" class="w-full rounded-xl border border-slate-200 px-4 py-2.5" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Nội dung</label>
                <textarea name="content" rows="10" class="w-full rounded-xl border border-slate-200 px-4 py-2.5" required></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Ảnh minh họa (không bắt buộc)</label>
                <input type="file" name="image" accept="image/*" class="block w-full text-sm text-slate-600">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white">Đăng bài</button>
                <a href="<?= URLROOT; ?>/posts" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600">Hủy</a>
            </div>
        </form>
    </div>
</div>
