<?php
$sets = is_array($sets ?? null) ? $sets : [];
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-5xl px-2 py-4 sm:px-4 space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h1 class="text-2xl font-bold text-slate-900">Thử thách Quiz</h1>
            <p class="mt-2 text-sm text-slate-600">Hoàn thành toàn bộ câu hỏi để nhận chứng nhận và cộng điểm uy tín.</p>
            <?php if (!is_logged_in()): ?>
                <a href="<?= URLROOT; ?>/login" class="mt-3 inline-flex rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white">Đăng nhập để lưu kết quả</a>
            <?php endif; ?>
        </div>

        <?php if ($sets !== []): ?>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <?php foreach ($sets as $set): ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-5">
                        <h2 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars((string) ($set['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="mt-1 text-sm text-slate-500">Chủ đề: <?= htmlspecialchars((string) ($set['topic'] ?? 'Tổng hợp'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mt-1 text-sm text-slate-500">Độ khó: <?= htmlspecialchars((string) ($set['difficulty'] ?? 'easy'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mt-1 text-sm text-slate-500">Thời gian: <?= (int) ($set['time_limit_minutes'] ?? 0) > 0 ? ((int) ($set['time_limit_minutes'] ?? 0) . ' phút') : 'Không giới hạn'; ?></p>
                        <p class="mt-1 text-sm text-slate-500">Số câu hỏi: <?= (int) ($set['question_count'] ?? 0); ?></p>
                        <p class="mt-3 text-sm text-slate-600 line-clamp-3"><?= htmlspecialchars((string) ($set['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <a href="<?= URLROOT; ?>/quizzes/<?= (int) ($set['id'] ?? 0); ?>" class="mt-4 inline-flex rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Làm quiz</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">Hiện chưa có bộ câu hỏi nào được phát hành.</div>
        <?php endif; ?>
    </div>
</section>
