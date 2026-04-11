<?php
$set = is_array($set ?? null) ? $set : [];
$questions = is_array($questions ?? null) ? $questions : [];
$certificate = is_array($certificate ?? null) ? $certificate : null;
$latestAttempt = is_array($latestAttempt ?? null) ? $latestAttempt : null;
$noticeText = (string) ($noticeText ?? '');
$cooldownRemainingSeconds = max(0, (int) ($cooldownRemainingSeconds ?? 0));
$timeLimitMinutes = max(0, (int) ($set['time_limit_minutes'] ?? 0));
$totalPoints = 0;
foreach ($questions as $q) {
    $totalPoints += max(1, (int) ($q['points'] ?? 1));
}

$typeLabel = static function (string $type): string {
    return match ($type) {
        'single_choice' => 'Trắc nghiệm 1 đáp án',
        'multiple_choice' => 'Chọn nhiều đáp án',
        'fill_blank' => 'Điền vào chỗ trống',
        'ordering' => 'Sắp xếp thứ tự',
        default => 'Câu hỏi',
    };
};
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-4xl px-2 py-4 sm:px-4 space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars((string) ($set['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="mt-2 text-sm text-slate-600"><?= htmlspecialchars((string) ($set['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="mt-3 flex flex-wrap gap-3 text-xs font-semibold text-slate-600">
                <span class="rounded-full bg-slate-100 px-3 py-1">Chủ đề: <?= htmlspecialchars((string) ($set['topic'] ?? 'Tổng hợp'), ENT_QUOTES, 'UTF-8'); ?></span>
                <?php
                $diff = strtolower((string) ($set['difficulty'] ?? 'easy'));
                $diffLabel = match($diff) {
                    'easy' => 'Dễ',
                    'medium' => 'Trung bình',
                    'hard' => 'Khó',
                    default => htmlspecialchars($diff, ENT_QUOTES, 'UTF-8')
                };
                ?>
                <span class="rounded-full bg-slate-100 px-3 py-1">Độ khó: <?= $diffLabel; ?></span>
                <span class="rounded-full bg-slate-100 px-3 py-1">Thời gian: <?= $timeLimitMinutes > 0 ? ($timeLimitMinutes . ' phút') : 'Không giới hạn'; ?></span>
                <span class="rounded-full bg-slate-100 px-3 py-1">Số câu: <?= count($questions); ?></span>
                <span class="rounded-full bg-slate-100 px-3 py-1">Tổng điểm: <?= $totalPoints; ?></span>
            </div>
        </div>

        <?php if ($noticeText !== ''): ?>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"><?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($certificate): ?>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                Đã có chứng nhận. Mã chứng nhận: <strong><?= htmlspecialchars((string) ($certificate['certificate_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($latestAttempt): ?>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                Lần làm gần nhất: <?= (int) ($latestAttempt['correct_answers'] ?? 0); ?>/<?= (int) ($latestAttempt['total_questions'] ?? 0); ?> câu đúng (<?= (float) ($latestAttempt['score_percent'] ?? 0); ?>%).
            </div>
        <?php endif; ?>

        <?php if (is_logged_in()): ?>
            <?php if ($cooldownRemainingSeconds > 0): ?>
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    Bạn chưa đạt ở lần gần nhất. Vui lòng chờ <?= $cooldownRemainingSeconds; ?> giây để thi lại.
                </div>
            <?php endif; ?>

            <?php if ($cooldownRemainingSeconds === 0): ?>
            <form method="post" action="<?= URLROOT; ?>/quizzes/<?= (int) ($set['id'] ?? 0); ?>/submit" class="space-y-4" id="quiz-submit-form">
                <?= csrf_field(); ?>
                <?php foreach ($questions as $index => $question): ?>
                    <?php
                    $type = (string) ($question['question_type'] ?? 'single_choice');
                    $choices = is_array($question['choices'] ?? null) ? $question['choices'] : [];
                    if ($type === 'ordering') {
                        shuffle($choices);
                    }
                    $questionId = (int) ($question['id'] ?? 0);
                    $questionPoints = max(1, (int) ($question['points'] ?? 1));
                    $questionImage = trim((string) ($question['question_image'] ?? ''));
                    $fillDecoded = $type === 'fill_blank' ? json_decode((string) ($question['answer_key_json'] ?? '[]'), true) : null;
                    $fillBlankCount = 1;
                    if (is_array($fillDecoded) && isset($fillDecoded['blanks']) && is_array($fillDecoded['blanks'])) {
                        $fillBlankCount = max(1, count($fillDecoded['blanks']));
                    }
                    ?>
                    <div class="quiz-question rounded-xl border border-slate-200 bg-white p-5" data-question-index="<?= $index; ?>">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-slate-900">Câu <?= $index + 1; ?>: <?= htmlspecialchars((string) ($question['question_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600"><?= htmlspecialchars($typeLabel($type), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700"><?= $questionPoints; ?> điểm</span>
                        </div>

                        <?php if ($questionImage !== ''): ?>
                            <img src="<?= URLROOT; ?>/uploads/<?= rawurlencode($questionImage); ?>" alt="Ảnh câu hỏi" class="mb-3 max-h-64 w-auto rounded border border-slate-300">
                        <?php endif; ?>

                        <div class="mt-3 space-y-2">
                            <?php if ($type === 'single_choice'): ?>
                                <?php foreach ($choices as $choice): ?>
                                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:border-primary">
                                        <input type="radio" required name="answers_single[<?= $questionId; ?>]" value="<?= (int) ($choice['id'] ?? 0); ?>" class="mt-0.5">
                                        <span><?= htmlspecialchars((string) ($choice['choice_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php elseif ($type === 'multiple_choice'): ?>
                                <p class="text-xs text-slate-500">Chọn tất cả đáp án đúng.</p>
                                <?php foreach ($choices as $choice): ?>
                                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:border-primary">
                                        <input type="checkbox" name="answers_multi[<?= $questionId; ?>][]" value="<?= (int) ($choice['id'] ?? 0); ?>" class="mt-0.5">
                                        <span><?= htmlspecialchars((string) ($choice['choice_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php elseif ($type === 'fill_blank'): ?>
                                <div class="space-y-2">
                                    <?php for ($blankNo = 1; $blankNo <= $fillBlankCount; $blankNo++): ?>
                                        <label class="block text-sm font-medium text-slate-700">
                                            Ô trống <?= $blankNo; ?>
                                            <input type="text" required name="answers_text[<?= $questionId; ?>][<?= $blankNo; ?>]" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nhập đáp án cho ô trống <?= $blankNo; ?>">
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            <?php elseif ($type === 'ordering'): ?>
                                <p class="text-xs text-slate-500">Gán thứ tự cho từng mục (1 là đầu tiên).</p>
                                <?php $maxRank = max(1, count($choices)); ?>
                                <?php foreach ($choices as $choice): ?>
                                    <?php $choiceId = (int) ($choice['id'] ?? 0); ?>
                                    <div class="grid grid-cols-[1fr_auto] items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                        <span><?= htmlspecialchars((string) ($choice['choice_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <select name="answers_order[<?= $questionId; ?>][<?= $choiceId; ?>]" class="rounded border border-slate-300 px-2 py-1 text-sm">
                                            <?php for ($rank = 1; $rank <= $maxRank; $rank++): ?>
                                                <option value="<?= $rank; ?>"><?= $rank; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="space-y-1">
                            <p id="quiz-page-indicator" class="text-sm font-medium text-slate-700"></p>
                            <?php if ($timeLimitMinutes > 0): ?>
                                <p id="quiz-timer" class="text-sm font-semibold text-rose-600">Thời gian còn lại: --:--</p>
                            <?php else: ?>
                                <p class="text-sm font-semibold text-slate-600">Không giới hạn thời gian</p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="quiz-prev-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Trang trước</button>
                            <button type="button" id="quiz-next-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Trang tiếp theo</button>
                            <button type="submit" id="quiz-submit-btn" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Nộp bài</button>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <script>
            (function () {
                const perPage = 3;
                const timeLimitSeconds = <?= $timeLimitMinutes; ?> * 60;
                const form = document.getElementById('quiz-submit-form');
                if (!form) return;

                const questionBlocks = Array.from(form.querySelectorAll('.quiz-question'));
                if (questionBlocks.length === 0) return;

                const totalPages = Math.ceil(questionBlocks.length / perPage);
                const pageIndicator = document.getElementById('quiz-page-indicator');
                const prevBtn = document.getElementById('quiz-prev-btn');
                const nextBtn = document.getElementById('quiz-next-btn');
                const submitBtn = document.getElementById('quiz-submit-btn');
                const timerEl = document.getElementById('quiz-timer');
                let currentPage = 1;
                let remainingSeconds = Math.max(0, timeLimitSeconds);
                let timeoutSubmitted = false;

                questionBlocks.forEach((block) => {
                    block.querySelectorAll('[required]').forEach((field) => {
                        field.setAttribute('data-original-required', '1');
                    });
                });

                function isFieldVisible(field) {
                    const style = window.getComputedStyle(field);
                    return style.display !== 'none' && style.visibility !== 'hidden';
                }

                function syncRequiredInVisiblePage() {
                    questionBlocks.forEach((block, idx) => {
                        const page = Math.floor(idx / perPage) + 1;
                        const active = page === currentPage;
                        block.querySelectorAll('[data-original-required="1"]').forEach((field) => {
                            if (active) {
                                field.setAttribute('required', 'required');
                            } else {
                                field.removeAttribute('required');
                            }
                        });
                    });
                }

                function validateCurrentPage() {
                    const start = (currentPage - 1) * perPage;
                    const end = Math.min(start + perPage, questionBlocks.length);
                    for (let i = start; i < end; i += 1) {
                        const fields = questionBlocks[i].querySelectorAll('[required]');
                        for (const field of fields) {
                            if (isFieldVisible(field) && !field.reportValidity()) {
                                return false;
                            }
                        }
                    }
                    return true;
                }

                function renderPage() {
                    const start = (currentPage - 1) * perPage;
                    const end = start + perPage;
                    questionBlocks.forEach((block, idx) => {
                        block.style.display = idx >= start && idx < end ? '' : 'none';
                    });

                    const fromQuestion = start + 1;
                    const toQuestion = Math.min(end, questionBlocks.length);
                    if (pageIndicator) {
                        pageIndicator.textContent = `Trang ${currentPage}/${totalPages} - Câu ${fromQuestion} đến ${toQuestion}`;
                    }
                    if (prevBtn) prevBtn.disabled = currentPage === 1;
                    if (nextBtn) nextBtn.style.display = currentPage === totalPages ? 'none' : '';
                    if (submitBtn) submitBtn.style.display = currentPage === totalPages ? '' : 'none';

                    syncRequiredInVisiblePage();
                }

                function formatTime(seconds) {
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                }

                function showAllPagesBeforeSubmit() {
                    questionBlocks.forEach((block) => {
                        block.style.display = '';
                    });
                    questionBlocks.forEach((block) => {
                        block.querySelectorAll('[data-original-required="1"]').forEach((field) => {
                            field.removeAttribute('required');
                        });
                    });
                }

                function forceSubmitWhenTimeout() {
                    if (timeoutSubmitted) return;
                    timeoutSubmitted = true;
                    if (timerEl) {
                        timerEl.textContent = 'Đã hết thời gian. Đang nộp bài...';
                    }
                    showAllPagesBeforeSubmit();
                    form.submit();
                }

                function startTimer() {
                    if (!timerEl || remainingSeconds <= 0) return;
                    timerEl.textContent = `Thời gian còn lại: ${formatTime(remainingSeconds)}`;
                    const timer = window.setInterval(() => {
                        remainingSeconds -= 1;
                        if (remainingSeconds <= 0) {
                            window.clearInterval(timer);
                            forceSubmitWhenTimeout();
                            return;
                        }
                        timerEl.textContent = `Thời gian còn lại: ${formatTime(remainingSeconds)}`;
                    }, 1000);
                }

                prevBtn?.addEventListener('click', () => {
                    if (currentPage > 1) {
                        currentPage -= 1;
                        renderPage();
                        window.scrollTo({ top: form.offsetTop - 24, behavior: 'smooth' });
                    }
                });

                nextBtn?.addEventListener('click', () => {
                    if (!validateCurrentPage()) return;
                    if (currentPage < totalPages) {
                        currentPage += 1;
                        renderPage();
                        window.scrollTo({ top: form.offsetTop - 24, behavior: 'smooth' });
                    }
                });

                form.addEventListener('submit', (event) => {
                    if (timeoutSubmitted) {
                        return;
                    }
                    syncRequiredInVisiblePage();
                    if (!validateCurrentPage()) {
                        event.preventDefault();
                    }
                });

                renderPage();
                startTimer();
            })();
            </script>
        <?php else: ?>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                Cần <a href="<?= URLROOT; ?>/login" class="font-semibold text-primary">đăng nhập</a> để nộp bài và nhận chứng nhận.
            </div>
        <?php endif; ?>
    </div>
</section>
