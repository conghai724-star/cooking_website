<?php
$set = is_array($set ?? null) ? $set : [];
$questions = is_array($questions ?? null) ? $questions : [];
$participants = is_array($participants ?? null) ? $participants : [];
$passers = is_array($passers ?? null) ? $passers : [];
$noticeText = (string) ($noticeText ?? '');
$showUserDetails = (bool) ($showUserDetails ?? false);

$initialQuestions = [];
foreach ($questions as $question) {
    $type = (string) ($question['question_type'] ?? 'single_choice');
    $choices = is_array($question['choices'] ?? null) ? $question['choices'] : [];

    $choiceTexts = [];
    $correctIndexes = [];
    foreach ($choices as $idx => $choice) {
        $choiceTexts[] = (string) ($choice['choice_text'] ?? '');
        if ((int) ($choice['is_correct'] ?? 0) === 1) {
            $correctIndexes[] = $idx + 1;
        }
    }

    $answerKey = '';
    if ($type === 'single_choice') {
        $answerKey = $correctIndexes !== [] ? (string) $correctIndexes[0] : '';
    } elseif ($type === 'multiple_choice') {
        $answerKey = implode(',', $correctIndexes);
    } elseif ($type === 'fill_blank') {
        $accepted = json_decode((string) ($question['answer_key_json'] ?? '[]'), true);
        if (!is_array($accepted) || !isset($accepted['blanks']) || !is_array($accepted['blanks'])) {
            if (is_array($accepted)) {
                $answerKey = implode(' | ', array_map(static fn($v): string => (string) $v, $accepted));
            }
        }
    }

    $fillBlankAnswers = [];
    $fillBlankCount = 1;
    if ($type === 'fill_blank') {
        $decoded = json_decode((string) ($question['answer_key_json'] ?? '[]'), true);
        if (is_array($decoded) && isset($decoded['blanks']) && is_array($decoded['blanks'])) {
            foreach ($decoded['blanks'] as $blank) {
                $vals = array_map(static fn($v): string => (string) $v, is_array($blank) ? $blank : []);
                $fillBlankAnswers[] = implode(' | ', $vals);
            }
            $fillBlankCount = max(1, count($fillBlankAnswers));
        } elseif ($answerKey !== '') {
            $fillBlankAnswers[] = $answerKey;
        }
    }

    $initialQuestions[] = [
        'question_type' => $type,
        'text' => (string) ($question['question_text'] ?? ''),
        'points' => max(1, (int) ($question['points'] ?? 1)),
        'answer_key' => $answerKey,
        'options' => $choiceTexts,
        'correct_single' => $type === 'single_choice' ? $answerKey : '',
        'correct_multi' => $type === 'multiple_choice' ? $correctIndexes : [],
        'option_count' => count($choiceTexts) > 0 ? count($choiceTexts) : 4,
        'correct_count' => $type === 'multiple_choice' ? max(1, count($correctIndexes)) : 1,
        'fill_template' => $type === 'fill_blank' ? (string) ($question['question_text'] ?? '') : '',
        'fill_blank_count' => $fillBlankCount,
        'fill_blank_answers' => $fillBlankAnswers,
        'ordering_count' => $type === 'ordering' ? max(2, count($choiceTexts)) : 4,
        'ordering_items' => $type === 'ordering' ? $choiceTexts : [],
        'ordering_positions' => $type === 'ordering' ? range(1, max(1, count($choiceTexts))) : [],
        'existing_image' => (string) ($question['question_image'] ?? ''),
        'explanation' => (string) ($question['explanation'] ?? ''),
    ];
}
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Cap nhat bo cau hoi</h1>
            <p class="text-sm text-slate-500">Moi cau hoi su dung form rieng theo loai da chon.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= URLROOT; ?>/admin/quizzes/<?= (int) ($set['id'] ?? 0); ?>/users" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Chi tiet user</a>
            <a href="<?= URLROOT; ?>/admin/quizzes" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Quay lai</a>
        </div>
    </div>

    <?php if ($showUserDetails): ?>
    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Danh sách tham gia</h2>
                <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700"><?= count($participants); ?> ngA�°A�»i</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500">
                            <th class="py-2 pr-3">NgA�°A�»i dĂ¹ng</th>
                            <th class="py-2 pr-3">LA�º§n lĂ m</th>
                            <th class="py-2 pr-3">Điểm cao nhất</th>
                            <th class="py-2 pr-3">LA�º§n gA�º§n nhA�º¥t</th>
                            <th class="py-2">TrA�º¡ng thĂ¡i</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($participants === []): ?>
                        <tr>
                            <td colspan="5" class="py-3 text-slate-500">ChA�°a cĂ³ ngA�°A�»i tham gia.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($participants as $participant): ?>
                            <?php
                            $name = trim((string) ($participant['user_name'] ?? ''));
                            $email = trim((string) ($participant['user_email'] ?? ''));
                            $label = $name !== '' ? $name : ($email !== '' ? $email : ('User #' . (int) ($participant['user_id'] ?? 0)));
                            $hasPassed = (int) ($participant['has_passed'] ?? 0) === 1;
                            $hasCertificate = trim((string) ($participant['certificate_code'] ?? '')) !== '';
                            ?>
                            <tr class="border-t border-slate-100">
                                <td class="py-2 pr-3">
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php if ($email !== '' && $email !== $label): ?>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 pr-3 text-slate-700"><?= (int) ($participant['attempts_count'] ?? 0); ?></td>
                                <td class="py-2 pr-3 text-slate-700"><?= number_format((float) ($participant['best_score_percent'] ?? 0), 2); ?>%</td>
                                <td class="py-2 pr-3 text-slate-700"><?= htmlspecialchars((string) ($participant['last_attempt_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="py-2 text-slate-700">
                                    <?php if ($hasCertificate): ?>
                                        <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">A�Ă£ A�‘A�º¡t</span>
                                    <?php elseif ($hasPassed): ?>
                                        <span class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Đã qua bài</span>
                                    <?php else: ?>
                                        <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">ChA�°a A�‘A�º¡t</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Danh sĂ¡ch A�‘Ă£ A�‘A�º¡t chA�»©ng chA�»‰</h2>
                <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700"><?= count($passers); ?> ngA�°A�»i</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500">
                            <th class="py-2 pr-3">NgA�°A�»i dĂ¹ng</th>
                            <th class="py-2 pr-3">Điểm</th>
                            <th class="py-2 pr-3">Uy tĂ­n cA�»™ng</th>
                            <th class="py-2 pr-3">MĂ£ chA�»©ng chA�»‰</th>
                            <th class="py-2">NgĂ y cA�º¥p</th>
                            <th class="py-2 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($passers === []): ?>
                        <tr>
                            <td colspan="6" class="py-3 text-slate-500">ChA�°a cĂ³ ngA�°A�»i A�‘A�º¡t chA�»©ng chA�»‰.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($passers as $passer): ?>
                            <?php
                            $name = trim((string) ($passer['user_name'] ?? ''));
                            $email = trim((string) ($passer['user_email'] ?? ''));
                            $label = $name !== '' ? $name : ($email !== '' ? $email : ('User #' . (int) ($passer['user_id'] ?? 0)));
                            ?>
                            <tr class="border-t border-slate-100">
                                <td class="py-2 pr-3">
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php if ($email !== '' && $email !== $label): ?>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 pr-3 text-slate-700"><?= number_format((float) ($passer['score_percent'] ?? 0), 2); ?>%</td>
                                <td class="py-2 pr-3 text-slate-700">+<?= (int) ($passer['awarded_reputation_points'] ?? 0); ?></td>
                                <td class="py-2 pr-3 text-slate-700"><?= htmlspecialchars((string) ($passer['certificate_code'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="py-2 text-slate-700"><?= htmlspecialchars((string) ($passer['awarded_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="py-2 text-right">
                                    <form method="post" action="<?= URLROOT; ?>/admin/quiz-certificates/<?= (int) ($passer['certificate_id'] ?? 0); ?>/delete" onsubmit="return confirm('BA�º¡n cĂ³ chA�º¯c muA�»‘n xĂ³a chA�»©ng nhA�º­n nĂ y?');">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="set_id" value="<?= (int) ($set['id'] ?? 0); ?>">
                                        <button type="submit" class="rounded border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">XĂ³a chA�»©ng nhA�º­n</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= URLROOT; ?>/admin/quizzes/<?= (int) ($set['id'] ?? 0); ?>/update" class="space-y-4" enctype="multipart/form-data" id="quiz-edit-form">
        <?= csrf_field(); ?>
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-3">
                <label class="text-sm font-medium text-slate-700">
                    Tieu de
                    <input type="text" name="title" required maxlength="255" value="<?= htmlspecialchars((string) ($set['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Chu de
                    <input type="text" name="topic" maxlength="120" value="<?= htmlspecialchars((string) ($set['topic'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    Do kho
                    <select name="difficulty" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <?php $difficulty = (string) ($set['difficulty'] ?? 'easy'); ?>
                        <option value="easy" <?= $difficulty === 'easy' ? 'selected' : ''; ?>>De</option>
                        <option value="medium" <?= $difficulty === 'medium' ? 'selected' : ''; ?>>Trung binh</option>
                        <option value="hard" <?= $difficulty === 'hard' ? 'selected' : ''; ?>>Kho</option>
                    </select>
                </label>
            </div>

            <label class="block text-sm font-medium text-slate-700">
                Mo ta
                <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= htmlspecialchars((string) ($set['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-sm font-semibold text-slate-800">Thoi gian lam bai</p>
                <p class="mt-1 text-xs text-slate-500">Nhap so phut. De 0 neu khong gioi han.</p>
                <label class="mt-3 block text-sm font-medium text-slate-700">
                    Gioi han thoi gian (phut)
                    <input type="number" min="0" max="600" step="1" name="time_limit_minutes" value="<?= (int) ($set['time_limit_minutes'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="0">
                </label>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-sm font-semibold text-slate-800">Dieu kien dat chung chi</p>
                <p class="mt-1 text-xs text-slate-500">De trong = mac dinh phai dung het cau va dat toi da diem.</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700">
                        So cau dung toi thieu
                        <input type="number" min="0" step="1" name="pass_min_correct" value="<?= (int) ($set['pass_min_correct'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="0">
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        So diem toi thieu
                        <input type="number" min="0" step="1" name="pass_min_points" value="<?= (int) ($set['pass_min_points'] ?? 0); ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="0">
                    </label>
                </div>
            </div>

            <div id="questions-container" class="space-y-3"></div>
            <div class="flex flex-wrap items-end gap-2">
                <label class="text-sm font-medium text-slate-700">
                    Chon form cau hoi
                    <select id="add-question-type" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="single_choice">Trac nghiem 1 dap an</option>
                        <option value="multiple_choice">Chon nhieu dap an</option>
                        <option value="fill_blank">Dien vao cho trong</option>
                        <option value="ordering">Sap xep thu tu</option>
                    </select>
                </label>
                <button type="button" id="quiz-prev-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Trang truoc</button>
                <button type="button" id="quiz-next-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Trang tiep theo</button>
                <p id="quiz-page-indicator" class="text-sm text-slate-600"></p>
                <button type="button" id="add-question-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">+ Them cau hoi</button>
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Luu cap nhat</button>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const initialQuestions = <?= json_encode($initialQuestions, JSON_UNESCAPED_UNICODE); ?> || [];
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const addQuestionType = document.getElementById('add-question-type');
    const prevBtn = document.getElementById('quiz-prev-btn');
    const nextBtn = document.getElementById('quiz-next-btn');
    const pageIndicator = document.getElementById('quiz-page-indicator');
    const form = document.getElementById('quiz-edit-form');
    const passMinCorrectInput = form?.querySelector('input[name="pass_min_correct"]') || null;
    const passMinPointsInput = form?.querySelector('input[name="pass_min_points"]') || null;
    const perPage = 3;
    let currentPage = 1;

    function typeLabel(type) {
        if (type === 'single_choice') return 'Trac nghiem 1 dap an';
        if (type === 'multiple_choice') return 'Chon nhieu dap an';
        if (type === 'fill_blank') return 'Dien vao cho trong';
        return 'Sap xep thu tu';
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    function fieldsTemplate(index, type, data) {
        const answerKey = escapeHtml(data.answer_key || '');
        if (type === 'single_choice') {
            const optionValues = [0, 1, 2, 3].map((i) => escapeHtml((data.options && data.options[i]) ? data.options[i] : ''));
            const selected = Number.parseInt(data.correct_single || answerKey || '1', 10);
            return `
                <input type="hidden" name="questions[${index}][choice_lines]" value="">
                <input type="hidden" name="questions[${index}][answer_key]" value="">
                <div class="grid gap-3 md:grid-cols-2">
                    ${[1, 2, 3, 4].map((n) => `
                        <label class="text-sm font-medium text-slate-700">
                            Dap an ${n}
                            <input type="text" name="questions[${index}][options][]" required value="${optionValues[n - 1]}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                    `).join('')}
                </div>
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-700">Dap an dung</p>
                    <div class="flex flex-wrap gap-3">
                        ${[1, 2, 3, 4].map((n) => `
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="questions[${index}][correct_single]" value="${n}" ${selected === n ? 'checked' : ''}>
                                <span>Dap an ${n}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        if (type === 'multiple_choice') {
            const maxOptions = 8;
            const optionValues = [];
            for (let i = 0; i < maxOptions; i += 1) optionValues.push(escapeHtml((data.options && data.options[i]) ? data.options[i] : ''));
            const selectedCorrect = Array.isArray(data.correct_multi) ? data.correct_multi.map((v) => Number.parseInt(v, 10)).filter((v) => Number.isInteger(v) && v > 0) : [];
            const optionCount = Math.min(maxOptions, Math.max(2, Number.parseInt(data.option_count || '4', 10) || 4));
            const correctCount = Math.min(optionCount, Math.max(1, Number.parseInt(data.correct_count || String(selectedCorrect.length || 2), 10) || 2));
            return `
                <input type="hidden" name="questions[${index}][choice_lines]" value="">
                <input type="hidden" name="questions[${index}][answer_key]" value="">
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700">
                        So dap an
                        <select name="questions[${index}][option_count]" class="mc-option-count mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            ${[2,3,4,5,6,7,8].map((n) => `<option value="${n}" ${n === optionCount ? 'selected' : ''}>${n}</option>`).join('')}
                        </select>
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        So dap an dung
                        <select name="questions[${index}][correct_count]" class="mc-correct-count mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-selected="${correctCount}"></select>
                    </label>
                </div>
                <div class="space-y-2">
                    ${[1,2,3,4,5,6,7,8].map((n) => `
                        <div class="mc-row grid grid-cols-[1fr_auto] items-center gap-3">
                            <label class="text-sm font-medium text-slate-700">
                                Dap an ${n}
                                <input type="text" name="questions[${index}][options][]" value="${optionValues[n - 1]}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-5">
                                <input type="checkbox" class="mc-correct" name="questions[${index}][correct_multi][]" value="${n}" ${selectedCorrect.includes(n) ? 'checked' : ''}>
                                <span>Dung</span>
                            </label>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        if (type === 'fill_blank') {
            const template = escapeHtml(data.fill_template || data.text || '');
            const blankCount = Math.min(6, Math.max(1, Number.parseInt(data.fill_blank_count || '1', 10) || 1));
            const blankAnswers = Array.isArray(data.fill_blank_answers) ? data.fill_blank_answers : [];
            return `
                <input type="hidden" name="questions[${index}][choice_lines]" value="">
                <input type="hidden" name="questions[${index}][answer_key]" value="">
                <label class="block text-sm font-medium text-slate-700">
                    Cau hoi mau (dung {{1}}, {{2}}...)
                    <textarea name="questions[${index}][fill_template]" rows="3" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">${template}</textarea>
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    So o trong
                    <select name="questions[${index}][fill_blank_count]" class="fill-blank-count mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        ${[1,2,3,4,5,6].map((n) => `<option value="${n}" ${n === blankCount ? 'selected' : ''}>${n}</option>`).join('')}
                    </select>
                </label>
                <div class="fill-blank-list space-y-2">
                    ${[1,2,3,4,5,6].map((n) => `
                        <label class="fill-blank-item block text-sm font-medium text-slate-700">
                            O trong ${n}
                            <input type="text" name="questions[${index}][fill_blank_answers][]" value="${escapeHtml(blankAnswers[n - 1] || '')}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                    `).join('')}
                </div>
            `;
        }

        const maxItems = 8;
        const itemValues = [];
        for (let i = 0; i < maxItems; i += 1) itemValues.push(escapeHtml((data.ordering_items && data.ordering_items[i]) ? data.ordering_items[i] : ''));
        const orderingPositions = Array.isArray(data.ordering_positions) ? data.ordering_positions : [];
        const itemCount = Math.min(maxItems, Math.max(2, Number.parseInt(data.ordering_count || '4', 10) || 4));
        return `
            <input type="hidden" name="questions[${index}][choice_lines]" value="">
            <input type="hidden" name="questions[${index}][answer_key]" value="">
            <label class="block text-sm font-medium text-slate-700">
                So muc sap xep
                <select name="questions[${index}][ordering_count]" class="ordering-count mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    ${[2,3,4,5,6,7,8].map((n) => `<option value="${n}" ${n === itemCount ? 'selected' : ''}>${n}</option>`).join('')}
                </select>
            </label>
            <div class="ordering-list space-y-2">
                ${[1,2,3,4,5,6,7,8].map((n) => `
                    <div class="ordering-row grid grid-cols-[1fr_auto] items-end gap-3">
                        <label class="text-sm font-medium text-slate-700">
                            Muc ${n}
                            <input type="text" name="questions[${index}][ordering_items][]" value="${itemValues[n - 1]}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                        <label class="text-sm font-medium text-slate-700">
                            Vi tri dung
                            <select name="questions[${index}][ordering_positions][]" class="ordering-position mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm" data-selected="${Number.parseInt(orderingPositions[n - 1] || String(n), 10) || n}"></select>
                        </label>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderQuestion(index, type, data = {}) {
        const existingImage = escapeHtml(data.existing_image || '');
        const text = escapeHtml(data.text || '');
        const points = Math.max(1, Number.parseInt(data.points || '1', 10) || 1);
        const explanation = escapeHtml(data.explanation || '');
        const imagePreview = existingImage ? `<div class="mt-2"><img src="<?= URLROOT; ?>/uploads/${encodeURIComponent(existingImage)}" class="h-24 rounded border border-slate-300" alt="Anh cau hoi"></div>` : '';
        const wrap = document.createElement('div');
        wrap.className = 'quiz-question rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-3';
        wrap.innerHTML = `
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-800">Cau hoi #${index + 1}</h3>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">${typeLabel(type)}</span>
                    <button type="button" class="remove-question rounded border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Xoa</button>
                </div>
            </div>
            <input type="hidden" name="questions[${index}][question_type]" value="${type}">
            <label class="block text-sm font-medium text-slate-700">
                Noi dung cau hoi
                <textarea name="questions[${index}][text]" rows="2" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">${text}</textarea>
            </label>
            <label class="block text-sm font-medium text-slate-700">
                Diem cau hoi
                <input type="number" min="1" step="1" name="questions[${index}][points]" value="${points}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label class="block text-sm font-medium text-slate-700">
                Anh cau hoi (khong bat buoc)
                <input type="file" name="question_images[${index}]" accept="image/*" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                <input type="hidden" name="questions[${index}][existing_image]" value="${existingImage}">
                ${imagePreview}
            </label>
            <div class="question-type-fields">${fieldsTemplate(index, type, data)}</div>
            <label class="block text-sm font-medium text-slate-700">
                Giai thich (khong bat buoc)
                <textarea name="questions[${index}][explanation]" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">${explanation}</textarea>
            </label>
        `;
        wrap.querySelector('.remove-question')?.addEventListener('click', () => {
            wrap.remove();
            reindexQuestions();
            updatePagination();
        });
        return wrap;
    }

    function setupFillBlankControls(scope) {
        const countEl = scope.querySelector('.fill-blank-count');
        const items = Array.from(scope.querySelectorAll('.fill-blank-item'));
        if (!countEl || items.length === 0) return;
        const apply = () => {
            const count = Math.min(6, Math.max(1, Number.parseInt(countEl.value || '1', 10) || 1));
            items.forEach((item, idx) => {
                const active = idx < count;
                item.style.display = active ? '' : 'none';
                item.querySelectorAll('input').forEach((input) => {
                    input.disabled = !active;
                    input.required = active;
                    if (!active) input.value = '';
                });
            });
        };
        countEl.addEventListener('change', apply);
        apply();
    }

    function setupOrderingControls(scope) {
        const countEl = scope.querySelector('.ordering-count');
        const rows = Array.from(scope.querySelectorAll('.ordering-row'));
        if (!countEl || rows.length === 0) return;
        const rebuild = () => {
            const count = Math.max(2, Number.parseInt(countEl.value || '4', 10) || 4);
            rows.forEach((row, idx) => {
                const select = row.querySelector('.ordering-position');
                if (!(select instanceof HTMLSelectElement)) return;
                const selectedAttr = Number.parseInt(select.getAttribute('data-selected') || String(idx + 1), 10) || (idx + 1);
                const current = Number.parseInt(select.value || String(selectedAttr), 10) || selectedAttr;
                select.innerHTML = '';
                for (let n = 1; n <= count; n += 1) {
                    const opt = document.createElement('option');
                    opt.value = String(n);
                    opt.textContent = String(n);
                    if (n === Math.min(count, Math.max(1, current))) opt.selected = true;
                    select.appendChild(opt);
                }
            });
        };
        const visibility = () => {
            const count = Math.max(2, Number.parseInt(countEl.value || '4', 10) || 4);
            rows.forEach((row, idx) => {
                const active = idx < count;
                row.style.display = active ? '' : 'none';
                row.querySelectorAll('input, select').forEach((field) => {
                    field.disabled = !active;
                    if (field instanceof HTMLInputElement && field.type === 'text') field.required = active;
                });
            });
        };
        countEl.addEventListener('change', () => {
            rebuild();
            visibility();
        });
        rebuild();
        visibility();
    }

    function setupMultipleChoiceControls(scope) {
        const optionCountEl = scope.querySelector('.mc-option-count');
        const correctCountEl = scope.querySelector('.mc-correct-count');
        const rows = Array.from(scope.querySelectorAll('.mc-row'));
        if (!optionCountEl || !correctCountEl || rows.length === 0) return;
        const rebuild = () => {
            const optionCount = Math.max(2, Number.parseInt(optionCountEl.value || '4', 10) || 4);
            const selected = Number.parseInt(correctCountEl.value || correctCountEl.getAttribute('data-selected') || '1', 10) || 1;
            correctCountEl.innerHTML = '';
            for (let n = 1; n <= optionCount; n += 1) {
                const opt = document.createElement('option');
                opt.value = String(n);
                opt.textContent = String(n);
                if (n === Math.min(optionCount, Math.max(1, selected))) opt.selected = true;
                correctCountEl.appendChild(opt);
            }
        };
        const visibility = () => {
            const optionCount = Math.max(2, Number.parseInt(optionCountEl.value || '4', 10) || 4);
            rows.forEach((row, idx) => {
                const active = idx < optionCount;
                row.style.display = active ? '' : 'none';
                row.querySelectorAll('input').forEach((input) => {
                    input.disabled = !active;
                    if (input.type === 'text') input.required = active;
                    if (!active && input.type === 'checkbox') input.checked = false;
                });
            });
        };
        const enforce = () => {
            const maxCorrect = Math.max(1, Number.parseInt(correctCountEl.value || '1', 10) || 1);
            const activeChecks = rows
                .filter((row) => row.style.display !== 'none')
                .flatMap((row) => Array.from(row.querySelectorAll('.mc-correct')))
                .filter((el) => el instanceof HTMLInputElement && !el.disabled);
            const checked = activeChecks.filter((el) => el.checked);
            if (checked.length > maxCorrect) checked.slice(maxCorrect).forEach((el) => { el.checked = false; });
        };
        optionCountEl.addEventListener('change', () => {
            rebuild();
            visibility();
            enforce();
        });
        correctCountEl.addEventListener('change', enforce);
        rows.forEach((row) => row.querySelectorAll('.mc-correct').forEach((el) => el.addEventListener('change', enforce)));
        rebuild();
        visibility();
        enforce();
    }

    function getQuestionBlocks() {
        return Array.from(questionsContainer.querySelectorAll('.quiz-question'));
    }

    function reindexQuestions() {
        getQuestionBlocks().forEach((block, index) => {
            const title = block.querySelector('h3');
            if (title) title.textContent = `Cau hoi #${index + 1}`;
            block.querySelectorAll('textarea, input, select').forEach((field) => {
                const name = field.getAttribute('name') || '';
                field.setAttribute('name', name.replace(/questions\[\d+\]/, `questions[${index}]`).replace(/question_images\[\d+\]/, `question_images[${index}]`));
            });
        });
    }

    function calculateQuizTotals() {
        const blocks = getQuestionBlocks();
        let totalPoints = 0;
        blocks.forEach((block) => {
            const pointsField = block.querySelector('input[name^="questions["][name$="[points]"]');
            const value = Number.parseInt((pointsField && pointsField.value) ? pointsField.value : '1', 10);
            totalPoints += Math.max(1, Number.isFinite(value) ? value : 1);
        });
        return { totalQuestions: blocks.length, totalPoints };
    }

    function syncPassLimits() {
        const { totalQuestions, totalPoints } = calculateQuizTotals();
        if (passMinCorrectInput) {
            passMinCorrectInput.max = String(Math.max(0, totalQuestions));
            const current = Math.max(0, Number.parseInt(passMinCorrectInput.value || '0', 10) || 0);
            if (current > totalQuestions) passMinCorrectInput.value = String(totalQuestions);
        }
        if (passMinPointsInput) {
            passMinPointsInput.max = String(Math.max(0, totalPoints));
            const current = Math.max(0, Number.parseInt(passMinPointsInput.value || '0', 10) || 0);
            if (current > totalPoints) passMinPointsInput.value = String(totalPoints);
        }
    }

    function updatePagination() {
        const blocks = getQuestionBlocks();
        const total = blocks.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        blocks.forEach((block, idx) => { block.style.display = idx >= start && idx < end ? '' : 'none'; });
        if (pageIndicator) {
            const from = total > 0 ? start + 1 : 0;
            const to = Math.min(end, total);
            pageIndicator.textContent = total > 0 ? `Trang ${currentPage}/${totalPages} - Cau ${from} den ${to}` : 'Trang 1/1';
        }
        if (prevBtn) prevBtn.disabled = currentPage === 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
        syncPassLimits();
    }

    function appendQuestion(type, data = {}) {
        const index = getQuestionBlocks().length;
        const node = renderQuestion(index, type, data);
        questionsContainer.appendChild(node);
        if (type === 'multiple_choice') setupMultipleChoiceControls(node);
        else if (type === 'fill_blank') setupFillBlankControls(node);
        else if (type === 'ordering') setupOrderingControls(node);
        updatePagination();
    }

    if (initialQuestions.length > 0) initialQuestions.forEach((q) => appendQuestion(q.question_type || 'single_choice', q));
    else appendQuestion('single_choice', {});

    addQuestionBtn?.addEventListener('click', () => appendQuestion(addQuestionType?.value || 'single_choice', {}));
    prevBtn?.addEventListener('click', () => { if (currentPage > 1) { currentPage -= 1; updatePagination(); } });
    nextBtn?.addEventListener('click', () => { currentPage += 1; updatePagination(); });
    form?.addEventListener('submit', () => { syncPassLimits(); getQuestionBlocks().forEach((b) => { b.style.display = ''; }); });
    questionsContainer.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.matches('input[name^="questions["][name$="[points]"]')) syncPassLimits();
    });

    updatePagination();
})();
</script>

