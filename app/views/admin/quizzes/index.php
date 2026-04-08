<?php
$sets = is_array($sets ?? null) ? $sets : [];
$participantPreviews = is_array($participantPreviews ?? null) ? $participantPreviews : [];
$passerPreviews = is_array($passerPreviews ?? null) ? $passerPreviews : [];
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">QuA�º£n lĂ½ bA�»™ cĂ¢u hA�»i</h1>
            <p class="text-sm text-slate-500">ThĂªm cĂ¢u hA�»i theo tA�»«ng form riĂªng: single, multiple, fill_blank, ordering.</p>
        </div>
    </div>

    <?php if (!empty($noticeText)): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?= htmlspecialchars((string) $noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">TA�º¡o bA�»™ cĂ¢u hA�»i mA�»›i</h2>
        <form method="post" action="<?= URLROOT; ?>/admin/quizzes/create" class="space-y-4" id="quiz-create-form" enctype="multipart/form-data">
            <?= csrf_field(); ?>
            <div class="grid gap-4 md:grid-cols-3">
                <label class="text-sm font-medium text-slate-700">
                    TiĂªu A�‘A�»
                    <input type="text" name="title" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    ChA�»§ A�‘A�»
                    <input type="text" name="topic" maxlength="120" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="VĂ­ dA�»¥: An toĂ n thA�»±c phA�º©m">
                </label>
                <label class="text-sm font-medium text-slate-700">
                    A�A�»™ khĂ³
                    <select name="difficulty" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="easy">DA�»…</option>
                        <option value="medium">Trung bình</option>
                        <option value="hard">Khó</option>
                    </select>
                </label>
            </div>

            <label class="block text-sm font-medium text-slate-700">
                MĂ´ tA�º£
                <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            </label>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-sm font-semibold text-slate-800">ThA�»i gian lĂ m bĂ i</p>
                <p class="mt-1 text-xs text-slate-500">NhA�º­p sA�»‘ phĂºt. NA�º¿u A�‘A�»ƒ 0 sA�º½ khĂ´ng giA�»›i hA�º¡n</p>
                <label class="mt-3 block text-sm font-medium text-slate-700">
                    GiA�»›i hA�º¡n thA�»i gian (phĂºt)
                    <input type="number" min="0" max="600" step="1" name="time_limit_minutes" value="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="0">
                </label>
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-sm font-semibold text-slate-800">Điều kiện đạt chứng chỉ</p>
                <p class="mt-1 text-xs text-slate-500">A�A�»ƒ trA�»‘ng = mA�º·c A�‘A�»‹nh phA�º£i A�‘Ăºng hA�º¿t cĂ¢u vĂ  A�‘A�º¡t tA�»‘i A�‘a A�‘iA�»ƒm.</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700">
                        SA�»‘ cĂ¢u A�‘Ăºng tA�»‘i thiA�»ƒu
                        <input type="number" min="0" step="1" name="pass_min_correct" value="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="0">
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        SA�»‘ A�‘iA�»ƒm tA�»‘i thiA�»ƒu
                        <input type="number" min="0" step="1" name="pass_min_points" value="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="0">
                    </label>
                </div>
            </div>

            <div class="space-y-3" id="questions-container"></div>

            <div class="flex flex-wrap items-end gap-2">
                <label class="text-sm font-medium text-slate-700">
                    ChA�»n form cĂ¢u hA�»i
                    <select id="add-question-type" class="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="single_choice">TrA�º¯c nghiA�»‡m 1 A�‘Ă¡p Ă¡n</option>
                        <option value="multiple_choice">ChA�»n nhiA�»u A�‘Ă¡p Ă¡n</option>
                        <option value="fill_blank">A�iA�»n vĂ o chA�»— trA�»‘ng</option>
                        <option value="ordering">SA�º¯p xA�º¿p thA�»© tA�»±</option>
                    </select>
                </label>
                <button type="button" id="quiz-prev-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Trang trA�°A�»›c</button>
                <button type="button" id="quiz-next-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Trang tiA�º¿p theo</button>
                <p id="quiz-page-indicator" class="text-sm text-slate-600"></p>
                <button type="button" id="add-question-btn" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">+ ThĂªm cĂ¢u hA�»i</button>
                <button type="submit" id="save-quiz-btn" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Lưu và phát hành</button>
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Danh sĂ¡ch bA�»™ cĂ¢u hA�»i</h2>
        <?php if (!empty($sets)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3">TiĂªu A�‘A�»</th>
                        <th class="px-4 py-3">ChA�»§ A�‘A�»</th>
                        <th class="px-4 py-3">CĂ¢u hA�»i</th>
                        <th class="px-4 py-3">LA�°A�»£t lĂ m</th>
                        <th class="px-4 py-3">ChA�»©ng nhA�º­n</th>
                        <th class="px-4 py-3">Hành động</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach ($sets as $set): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-800"><?= htmlspecialchars((string) ($set['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars((string) ($set['topic'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= (int) ($set['question_count'] ?? 0); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= (int) ($set['attempt_count'] ?? 0); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= (int) ($set['certificate_count'] ?? 0); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2 whitespace-nowrap">
                                    <a class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/admin/quizzes/<?= (int) ($set['id'] ?? 0); ?>">SA�»­a</a>
                                    <form method="post" action="<?= URLROOT; ?>/admin/quizzes/<?= (int) ($set['id'] ?? 0); ?>/delete" onsubmit="return confirm('BA�º¡n cĂ³ chA�º¯c muA�»‘n xĂ³a bA�»™ cĂ¢u hA�»i nĂ y?');">
                                        <?= csrf_field(); ?>
                                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">ChA�°a cĂ³ bA�»™ cĂ¢u hA�»i nĂ o.</div>
        <?php endif; ?>
    </div>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-5">
    <h2 class="mb-4 text-lg font-semibold text-slate-900">Xem nhanh ngA�°A�»i tham gia vĂ  ngA�°A�»i A�‘Ă£ A�‘A�º¡t</h2>
    <?php if ($sets === []): ?>
        <p class="text-sm text-slate-600">ChA�°a cĂ³ dA�»¯ liA�»‡u.</p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($sets as $set): ?>
                <?php
                $setId = (int) ($set['id'] ?? 0);
                $participantNames = is_array($participantPreviews[$setId] ?? null) ? $participantPreviews[$setId] : [];
                $passerNames = is_array($passerPreviews[$setId] ?? null) ? $passerPreviews[$setId] : [];
                ?>
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars((string) ($set['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <a class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/admin/quizzes/<?= $setId; ?>/users">Chi tiA�º¿t user</a>
                    </div>
                    <div class="mt-2 grid gap-2 text-sm md:grid-cols-2">
                        <div>
                            <p class="font-medium text-slate-700">Đã tham gia (<?= (int) ($set['attempt_count'] ?? 0); ?>)</p>
                            <p class="text-slate-600">
                                <?= $participantNames === [] ? 'Chưa có' : htmlspecialchars(implode(', ', $participantNames), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div>
                            <p class="font-medium text-slate-700">A�Ă£ A�‘A�º¡t chA�»©ng chA�»‰ (<?= (int) ($set['certificate_count'] ?? 0); ?>)</p>
                            <p class="text-slate-600">
                                <?= $passerNames === [] ? 'Chưa có' : htmlspecialchars(implode(', ', $passerNames), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question-btn');
    const addQuestionType = document.getElementById('add-question-type');
    const prevBtn = document.getElementById('quiz-prev-btn');
    const nextBtn = document.getElementById('quiz-next-btn');
    const pageIndicator = document.getElementById('quiz-page-indicator');
    const form = document.getElementById('quiz-create-form');
    const passMinCorrectInput = form?.querySelector('input[name="pass_min_correct"]') || null;
    const passMinPointsInput = form?.querySelector('input[name="pass_min_points"]') || null;
    const perPage = 3;
    let currentPage = 1;

    function typeLabel(type) {
        if (type === 'single_choice') return 'TrA�º¯c nghiA�»‡m 1 A�‘Ă¡p Ă¡n';
        if (type === 'multiple_choice') return 'ChA�»n nhiA�»u A�‘Ă¡p Ă¡n';
        if (type === 'fill_blank') return 'A�iA�»n vĂ o chA�»— trA�»‘ng';
        return 'SA�º¯p xA�º¿p thA�»© tA�»±';
    }

    function fieldsTemplate(index, type, data) {
        const choiceLines = escapeHtml(data.choice_lines || '');
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
                            Đáp án ${n}
                            <input type="text" name="questions[${index}][options][]" required value="${optionValues[n - 1]}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                    `).join('')}
                </div>
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-700">Đáp án đúng</p>
                    <div class="flex flex-wrap gap-3">
                        ${[1, 2, 3, 4].map((n) => `
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="questions[${index}][correct_single]" value="${n}" ${selected === n ? 'checked' : ''}>
                                <span>Đáp án ${n}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        if (type === 'multiple_choice') {
            const maxOptions = 8;
            const optionValues = [];
            for (let i = 0; i < maxOptions; i += 1) {
                optionValues.push(escapeHtml((data.options && data.options[i]) ? data.options[i] : ''));
            }
            const selectedCorrect = Array.isArray(data.correct_multi)
                ? data.correct_multi.map((v) => Number.parseInt(v, 10)).filter((v) => Number.isInteger(v) && v > 0)
                : [];
            const optionCount = Math.min(maxOptions, Math.max(2, Number.parseInt(data.option_count || '4', 10) || 4));
            const correctCount = Math.min(optionCount, Math.max(1, Number.parseInt(data.correct_count || String(selectedCorrect.length || 2), 10) || 2));
            return `
                <input type="hidden" name="questions[${index}][choice_lines]" value="">
                <input type="hidden" name="questions[${index}][answer_key]" value="">
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700">
                        Số đáp án
                        <select name="questions[${index}][option_count]" class="mc-option-count mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            ${[2,3,4,5,6,7,8].map((n) => `<option value="${n}" ${n === optionCount ? 'selected' : ''}>${n}</option>`).join('')}
                        </select>
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        Số đáp án đúng
                        <select name="questions[${index}][correct_count]" class="mc-correct-count mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-selected="${correctCount}"></select>
                    </label>
                </div>
                <div class="space-y-2">
                    ${[1,2,3,4,5,6,7,8].map((n) => `
                        <div class="mc-row grid grid-cols-[1fr_auto] items-center gap-3" data-option-no="${n}">
                            <label class="text-sm font-medium text-slate-700">
                                Đáp án ${n}
                                <input type="text" name="questions[${index}][options][]" value="${optionValues[n - 1]}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700 mt-5">
                                <input type="checkbox" class="mc-correct" name="questions[${index}][correct_multi][]" value="${n}" ${selectedCorrect.includes(n) ? 'checked' : ''}>
                                <span>Đúng</span>
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
                    CĂ¢u hA�»i mA�º«u (dĂ¹ng {{1}}, {{2}}... cho vA�»‹ trĂ­ trA�»‘ng)
                    <textarea name="questions[${index}][fill_template]" rows="3" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="VĂ­ dA�»¥: Món nĂ y cĂ³ {{1}} vĂ  {{2}}.">${template}</textarea>
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    SA�»‘ Ă´ trA�»‘ng
                    <select name="questions[${index}][fill_blank_count]" class="fill-blank-count mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        ${[1,2,3,4,5,6].map((n) => `<option value="${n}" ${n === blankCount ? 'selected' : ''}>${n}</option>`).join('')}
                    </select>
                </label>
                <div class="fill-blank-list space-y-2">
                    ${[1,2,3,4,5,6].map((n) => `
                        <label class="fill-blank-item block text-sm font-medium text-slate-700" data-blank-no="${n}">
                            Ă” trA�»‘ng ${n} (nhiA�»u A�‘Ă¡p Ă¡n ngA�ƒn cĂ¡ch bA�º±ng |)
                            <input type="text" name="questions[${index}][fill_blank_answers][]" value="${escapeHtml(blankAnswers[n - 1] || '')}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="VĂ­ dA�»¥: nA�°A�»›c mA�º¯m|nuoc mam">
                        </label>
                    `).join('')}
                </div>
            `;
        }

        const maxItems = 8;
        const itemValues = [];
        for (let i = 0; i < maxItems; i += 1) {
            itemValues.push(escapeHtml((data.ordering_items && data.ordering_items[i]) ? data.ordering_items[i] : ''));
        }
        const itemCount = Math.min(maxItems, Math.max(2, Number.parseInt(data.ordering_count || '4', 10) || 4));
        return `
            <input type="hidden" name="questions[${index}][choice_lines]" value="">
            <input type="hidden" name="questions[${index}][answer_key]" value="">
            <label class="block text-sm font-medium text-slate-700">
                SA�»‘ mA�»¥c sA�º¯p xA�º¿p
                <select name="questions[${index}][ordering_count]" class="ordering-count mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    ${[2,3,4,5,6,7,8].map((n) => `<option value="${n}" ${n === itemCount ? 'selected' : ''}>${n}</option>`).join('')}
                </select>
            </label>
            <div class="ordering-list space-y-2">
                ${[1,2,3,4,5,6,7,8].map((n) => `
                    <div class="ordering-row grid grid-cols-[1fr_auto] items-end gap-3" data-item-no="${n}">
                        <label class="text-sm font-medium text-slate-700">
                            MA�»¥c ${n}
                            <input type="text" name="questions[${index}][ordering_items][]" value="${itemValues[n - 1]}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </label>
                        <label class="text-sm font-medium text-slate-700">
                            Vượt qua đúng
                            <select name="questions[${index}][ordering_positions][]" class="ordering-position mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm" data-selected="${n}">
                            </select>
                        </label>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderQuestion(index, type) {
        const wrap = document.createElement('div');
        wrap.className = 'quiz-question rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-3';
        wrap.innerHTML = `
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-slate-800">CĂ¢u hA�»i #${index + 1}</h3>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">${typeLabel(type)}</span>
                    <button type="button" class="remove-question rounded border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Xóa</button>
                </div>
            </div>

            <input type="hidden" name="questions[${index}][question_type]" value="${type}">

            <label class="block text-sm font-medium text-slate-700">
                NA�»™i dung cĂ¢u hA�»i
                <textarea name="questions[${index}][text]" rows="2" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            </label>

            <label class="block text-sm font-medium text-slate-700">
                A�iA�»ƒm cĂ¢u hA�»i
                <input type="number" min="1" step="1" name="questions[${index}][points]" value="1" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </label>

            <label class="block text-sm font-medium text-slate-700">
                A�º¢nh cĂ¢u hA�»i (khĂ´ng bA�º¯t buA�»™c)
                <input type="file" name="question_images[${index}]" accept="image/*" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white">
                <input type="hidden" name="questions[${index}][existing_image]" value="">
            </label>

            <div class="question-type-fields">
                ${fieldsTemplate(index, type, {})}
            </div>

            <label class="block text-sm font-medium text-slate-700">
                GiA�º£i thĂ­ch (khĂ´ng bA�º¯t buA�»™c)
                <textarea name="questions[${index}][explanation]" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
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

        const rebuildPositionOptions = () => {
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
                    if (n === Math.min(count, Math.max(1, current))) {
                        opt.selected = true;
                    }
                    select.appendChild(opt);
                }
            });
        };

        const applyVisibility = () => {
            const count = Math.max(2, Number.parseInt(countEl.value || '4', 10) || 4);
            rows.forEach((row, idx) => {
                const active = idx < count;
                row.style.display = active ? '' : 'none';
                row.querySelectorAll('input, select').forEach((field) => {
                    field.disabled = !active;
                    if (field instanceof HTMLInputElement && field.type === 'text') {
                        field.required = active;
                    }
                });
            });
        };

        countEl.addEventListener('change', () => {
            rebuildPositionOptions();
            applyVisibility();
        });

        rebuildPositionOptions();
        applyVisibility();
    }

    function setupMultipleChoiceControls(scope) {
        const optionCountEl = scope.querySelector('.mc-option-count');
        const correctCountEl = scope.querySelector('.mc-correct-count');
        const rows = Array.from(scope.querySelectorAll('.mc-row'));
        if (!optionCountEl || !correctCountEl || rows.length === 0) return;

        const rebuildCorrectCountOptions = () => {
            const optionCount = Math.max(2, Number.parseInt(optionCountEl.value || '4', 10) || 4);
            const selected = Number.parseInt(correctCountEl.value || correctCountEl.getAttribute('data-selected') || '1', 10) || 1;
            correctCountEl.innerHTML = '';
            for (let n = 1; n <= optionCount; n += 1) {
                const opt = document.createElement('option');
                opt.value = String(n);
                opt.textContent = String(n);
                if (n === Math.min(optionCount, Math.max(1, selected))) {
                    opt.selected = true;
                }
                correctCountEl.appendChild(opt);
            }
        };

        const applyVisibility = () => {
            const optionCount = Math.max(2, Number.parseInt(optionCountEl.value || '4', 10) || 4);
            rows.forEach((row, idx) => {
                const active = idx < optionCount;
                row.style.display = active ? '' : 'none';
                row.querySelectorAll('input').forEach((input) => {
                    input.disabled = !active;
                    if (input.type === 'text') {
                        input.required = active;
                    }
                    if (!active && input.type === 'checkbox') {
                        input.checked = false;
                    }
                });
            });
        };

        const enforceCorrectCount = () => {
            const maxCorrect = Math.max(1, Number.parseInt(correctCountEl.value || '1', 10) || 1);
            const activeChecks = rows
                .filter((row) => row.style.display !== 'none')
                .flatMap((row) => Array.from(row.querySelectorAll('.mc-correct')))
                .filter((el) => el instanceof HTMLInputElement && !el.disabled);
            const checked = activeChecks.filter((el) => el.checked);
            if (checked.length > maxCorrect) {
                checked.slice(maxCorrect).forEach((el) => { el.checked = false; });
            }
        };

        optionCountEl.addEventListener('change', () => {
            rebuildCorrectCountOptions();
            applyVisibility();
            enforceCorrectCount();
        });
        correctCountEl.addEventListener('change', enforceCorrectCount);
        rows.forEach((row) => {
            row.querySelectorAll('.mc-correct').forEach((el) => {
                el.addEventListener('change', enforceCorrectCount);
            });
        });

        rebuildCorrectCountOptions();
        applyVisibility();
        enforceCorrectCount();
    }

    function reindexQuestions() {
        const blocks = questionsContainer.querySelectorAll('.quiz-question');
        blocks.forEach((block, index) => {
            const title = block.querySelector('h3');
            if (title) title.textContent = `CĂ¢u hA�»i #${index + 1}`;
            block.querySelectorAll('textarea, input, select').forEach((field) => {
                const name = field.getAttribute('name') || '';
                field.setAttribute('name', name.replace(/questions\[\d+\]/, `questions[${index}]`).replace(/question_images\[\d+\]/, `question_images[${index}]`));
            });
        });
    }

    function getQuestionBlocks() {
        return Array.from(questionsContainer.querySelectorAll('.quiz-question'));
    }

    function validateCurrentPage() {
        const blocks = getQuestionBlocks();
        const start = (currentPage - 1) * perPage;
        const end = Math.min(start + perPage, blocks.length);
        for (let i = start; i < end; i += 1) {
            const fields = blocks[i].querySelectorAll('input, select, textarea');
            for (const field of fields) {
                if (field instanceof HTMLElement && !field.disabled && field.willValidate && !field.reportValidity()) {
                    return false;
                }
            }
        }
        return true;
    }

    function showAllQuestions() {
        getQuestionBlocks().forEach((block) => {
            block.style.display = '';
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
        return {
            totalQuestions: blocks.length,
            totalPoints,
        };
    }

    function syncPassConditionLimits() {
        const { totalQuestions, totalPoints } = calculateQuizTotals();

        if (passMinCorrectInput) {
            passMinCorrectInput.max = String(Math.max(0, totalQuestions));
            const current = Math.max(0, Number.parseInt(passMinCorrectInput.value || '0', 10) || 0);
            if (current > totalQuestions) {
                passMinCorrectInput.value = String(totalQuestions);
            }
        }

        if (passMinPointsInput) {
            passMinPointsInput.max = String(Math.max(0, totalPoints));
            const current = Math.max(0, Number.parseInt(passMinPointsInput.value || '0', 10) || 0);
            if (current > totalPoints) {
                passMinPointsInput.value = String(totalPoints);
            }
        }
    }

    function updatePagination() {
        const blocks = getQuestionBlocks();
        const total = blocks.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        blocks.forEach((block, idx) => {
            block.style.display = idx >= start && idx < end ? '' : 'none';
        });

        if (pageIndicator) {
            const from = total > 0 ? start + 1 : 0;
            const to = Math.min(end, total);
            pageIndicator.textContent = total > 0
                ? `Trang ${currentPage}/${totalPages} - CĂ¢u ${from} A�‘A�º¿n ${to}`
                : 'Trang 1/1';
        }
        if (prevBtn) prevBtn.disabled = currentPage === 1;
        if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
        syncPassConditionLimits();
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    }

    addQuestionBtn?.addEventListener('click', () => {
        const index = questionsContainer.children.length;
        const type = addQuestionType?.value || 'single_choice';
        const question = renderQuestion(index, type);
        questionsContainer.appendChild(question);
        if (type === 'multiple_choice') {
            setupMultipleChoiceControls(question);
        } else if (type === 'fill_blank') {
            setupFillBlankControls(question);
        } else if (type === 'ordering') {
            setupOrderingControls(question);
        }
        updatePagination();
    });

    prevBtn?.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage -= 1;
            updatePagination();
        }
    });

    nextBtn?.addEventListener('click', () => {
        if (!validateCurrentPage()) return;
        currentPage += 1;
        updatePagination();
    });

    form?.addEventListener('submit', () => {
        syncPassConditionLimits();
        showAllQuestions();
    });

    questionsContainer.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        if (target.matches('input[name^="questions["][name$="[points]"]')) {
            syncPassConditionLimits();
        }
    });

    const first = renderQuestion(0, 'single_choice');
    questionsContainer.appendChild(first);
    updatePagination();
})();
</script>


