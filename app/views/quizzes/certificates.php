<?php
$certificates = is_array($certificates ?? null) ? $certificates : [];
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-4xl px-2 py-4 sm:px-4 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Chứng nhận của tôi</h1>
            <p class="text-sm text-slate-600">Danh sách chứng nhận quiz đã đạt.</p>
        </div>

        <?php if ($certificates !== []): ?>
            <div class="space-y-3">
                <?php foreach ($certificates as $certificate): ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars((string) ($certificate['quiz_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="mt-1 text-sm text-slate-500">Chủ đề: <?= htmlspecialchars((string) ($certificate['quiz_topic'] ?? 'Tổng hợp'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mt-1 text-sm text-slate-600">Mã chứng nhận: <strong><?= htmlspecialchars((string) ($certificate['certificate_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                        <p class="mt-1 text-sm text-slate-600">Điểm đạt: <?= (float) ($certificate['score_percent'] ?? 0); ?>% | Uy tín nhận: +<?= (int) ($certificate['awarded_reputation_points'] ?? 0); ?></p>
                        <p class="mt-1 text-xs text-slate-400">Thời gian cấp: <?= htmlspecialchars((string) ($certificate['awarded_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">Bạn chưa có chứng nhận nào. Hãy làm quiz tại <a href="<?= URLROOT; ?>/quizzes" class="font-semibold text-primary">đây</a>.</div>
        <?php endif; ?>
    </div>
</section>
