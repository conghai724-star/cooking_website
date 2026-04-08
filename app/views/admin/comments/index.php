<?php
$comments = is_array($comments ?? null) ? $comments : [];
$status = (string) ($status ?? '');
$keyword = (string) ($keyword ?? '');
$reportedOnly = !empty($reportedOnly);
$notice = (string) ($notice ?? '');

$noticeMap = [
    'hidden' => 'Da an binh luan.',
    'restored' => 'Da khoi phuc binh luan.',
    'deleted' => 'Da xoa binh luan.',
    'penalty_applied' => 'Da ap dung hinh thuc xu ly nguoi vi pham.',
    'penalty_failed' => 'Không thể ap dung hinh thuc xu ly.',
];
$noticeText = $noticeMap[$notice] ?? '';
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quan ly binh luan</h1>
        <p class="text-sm text-slate-500">An, xoa, khoi phuc binh luan vi pham.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <?php require APPROOT . '/app/views/admin/partials/comments_filters.php'; ?>
        <?php require APPROOT . '/app/views/admin/partials/comments_table.php'; ?>
    </div>
</div>

