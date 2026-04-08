<?php
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];

$statusLabel = static function (string $status): string {
    return match ($status) {
        'approved' => 'Đã duyệt',
        'rejected' => 'Từ chối',
        'pending' => 'Chờ duyệt',
        default => 'Nháp',
    };
};

$statusClass = static function (string $status): string {
    return match ($status) {
        'approved' => 'bg-emerald-100 text-emerald-700',
        'rejected' => 'bg-rose-100 text-rose-700',
        'pending' => 'bg-yellow-100 text-yellow-700',
        default => 'bg-slate-100 text-slate-600',
    };
};
?>

<div class="w-full">
    <div class="mx-auto w-full max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Nguyên liệu của tôi</h1>
                <p class="text-sm text-slate-500">Theo dõi trạng thái duyệt và gửi lại nếu bị từ chối.</p>
            </div>
            <a class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white" href="<?= URLROOT; ?>/ingredients/create">Góp ý mới</a>
        </div>

        <?php if (empty($ingredients)): ?>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Bạn chưa gửi nguyên liệu nào.</div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($ingredients as $ingredient): ?>
                    <?php $status = (string) ($ingredient['status'] ?? 'pending'); ?>
                    <div class="py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($ingredient['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="mt-1 inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass($status); ?>"><?= $statusLabel($status); ?></span>
                            <?php if ($status === 'rejected' && !empty($ingredient['rejection_reason'])): ?>
                                <p class="mt-2 text-xs text-rose-600">Lý do: <?= htmlspecialchars((string) $ingredient['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2">
                            <a class="rounded-md border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/ingredients/<?= (int) $ingredient['id']; ?>">Xem</a>
                            <?php if ($status === 'rejected'): ?>
                                <form method="post" action="<?= URLROOT; ?>/ingredients/<?= (int) $ingredient['id']; ?>/resubmit">
                                    <?= csrf_field(); ?>
                                    <button class="rounded-md bg-primary px-3 py-1 text-xs font-semibold text-white" type="submit">Gửi lại</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
