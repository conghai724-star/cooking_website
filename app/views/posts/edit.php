<?php
$post = is_array($post ?? null) ? $post : [];
$error = (string) ($error ?? '');
?>

<div class="w-full">
    <div class="mx-auto max-w-3xl">
        <h1 class="mb-6 text-4xl font-black text-slate-900">Sua bai viet</h1>

        <?php if ($error !== ''): ?>
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
            <?= csrf_field(); ?>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Tieu de</label>
                <input type="text" name="title" value="<?= htmlspecialchars((string) ($post['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5" required>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Noi dung</label>
                <textarea name="content" rows="10" class="w-full rounded-xl border border-slate-200 px-4 py-2.5" required><?= htmlspecialchars((string) ($post['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Anh minh hoa (khong bat buoc)</label>
                <input type="file" name="image" accept="image/*" class="block w-full text-sm text-slate-600">
                <?php if (!empty($post['image'])): ?>
                    <p class="mt-1 text-xs text-slate-500">Anh hien tai: <?= htmlspecialchars((string) $post['image'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white">Cap nhat</button>
                <a href="<?= URLROOT; ?>/posts/<?= (int) ($post['id'] ?? 0); ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600">Huy</a>
            </div>
        </form>
    </div>
</div>
