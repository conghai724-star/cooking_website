<?php
$tips = is_array($tips ?? null) ? $tips : [];

$badgeClass = static function (string $status): array {
    return match ($status) {
        'approved' => ['ĐA� duyệt', 'bg-emerald-100 text-emerald-700'],
        'rejected' => ['Tá»« chá»‘i', 'bg-rose-100 text-rose-700'],
        default => ['Chá» duyá»‡t', 'bg-yellow-100 text-yellow-700'],
    };
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lý mẹo vặt</h1>
        <p class="text-sm text-slate-500">Táº¡o, duyá»‡t vĂ  cáº­p nháº­t máº¹o váº·t do ngÆ°á»i dĂ¹ng gá»­i.</p>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Danh sĂ¡ch máº¹o váº·t</h3>
        </div>
        <?php if (empty($tips)): ?>
            <div class="p-6 text-sm text-slate-500">ChÆ°a cĂ³ máº¹o váº·t nĂ o.</div>
        <?php else: ?>
            <div class="divide-y divide-slate-100">
                <?php foreach ($tips as $tip): ?>
                    <?php
                    $status = (string) ($tip['status'] ?? 'pending');
                    [$statusLabel, $statusClass] = $badgeClass($status);
                    ?>
                    <div class="p-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($tip['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="text-xs text-slate-500">TĂ¡c giáº£: <?= htmlspecialchars((string) ($tip['author_name'] ?? 'NgÆ°á»i dĂ¹ng'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if ($status === 'rejected' && !empty($tip['rejection_reason'])): ?>
                                <p class="mt-2 text-xs text-rose-600">LĂ½ do: <?= htmlspecialchars((string) $tip['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass; ?>"><?= $statusLabel; ?></span>
                            <?php if ($status !== 'approved'): ?>
                                <form method="post" action="<?= URLROOT; ?>/admin/tips/<?= (int) $tip['id']; ?>/approve">
                                    <?= csrf_field(); ?>
                                    <button class="rounded-md bg-emerald-500 px-3 py-1 text-xs font-semibold text-white" type="submit">Duyá»‡t</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status === 'pending'): ?>
                                <form method="post" action="<?= URLROOT; ?>/admin/tips/<?= (int) $tip['id']; ?>/reject" class="flex items-center gap-2">
                                    <?= csrf_field(); ?>
                                    <input class="w-40 rounded-md border border-slate-200 px-2 py-1 text-xs" name="reason" placeholder="LĂ½ do tá»« chá»‘i">
                                    <button class="rounded-md bg-rose-500 px-3 py-1 text-xs font-semibold text-white" type="submit">Tá»« chá»‘i</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= URLROOT; ?>/admin/tips/<?= (int) $tip['id']; ?>/delete" onsubmit="return confirm('XĂ³a máº¹o váº·t nĂ y?');">
                                <?= csrf_field(); ?>
                                <button class="rounded-md border border-rose-300 px-3 py-1 text-xs font-semibold text-rose-600" type="submit">XĂ³a</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


