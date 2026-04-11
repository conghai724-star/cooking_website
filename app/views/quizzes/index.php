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

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <label for="quiz-search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tìm bộ quiz</label>
            <input id="quiz-search" name="keyword" type="search" placeholder="Nhập tên quiz, chủ đề, độ khó..." class="h-11 w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary">
        </div>

        <?php if ($sets !== []): ?>
            <div id="quiz-card-grid" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <?php foreach ($sets as $set): ?>
                    <?php
                    $searchText = mb_strtolower(trim((string) (($set['title'] ?? '') . ' ' . ($set['topic'] ?? '') . ' ' . ($set['difficulty'] ?? ''))), 'UTF-8');
                    ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-5" data-quiz-item data-quiz-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>">
                        <h2 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars((string) ($set['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="mt-1 text-sm text-slate-500">Chủ đề: <?= htmlspecialchars((string) ($set['topic'] ?? 'Tổng hợp'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php
                        $diff = strtolower((string) ($set['difficulty'] ?? 'easy'));
                        $diffLabel = match($diff) {
                            'easy' => 'Dễ',
                            'medium' => 'Trung bình',
                            'hard' => 'Khó',
                            default => htmlspecialchars($diff, ENT_QUOTES, 'UTF-8')
                        };
                        ?>
                        <p class="mt-1 text-sm text-slate-500">Độ khó: <?= $diffLabel; ?></p>
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

<script>
(() => {
    const input = document.getElementById('quiz-search');
    if (!input) return;
    const cards = Array.from(document.querySelectorAll('[data-quiz-item]'));
    input.addEventListener('input', () => {
        const keyword = String(input.value || '').trim().toLowerCase();
        cards.forEach((card) => {
            const haystack = String(card.getAttribute('data-quiz-search') || '');
            card.classList.toggle('hidden', keyword !== '' && !haystack.includes(keyword));
        });
    });
})();
</script>
