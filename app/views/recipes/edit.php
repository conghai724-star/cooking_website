<?php
$selectedTagIds = array_values(array_unique(array_map('intval', (array) ($selected_tag_ids ?? []))));
$tagsByType = is_array($tags_by_type ?? null) ? $tags_by_type : [];
$tagTypeLabels = [
    'method' => 'Cach nau',
    'taste' => 'Huong vi',
    'health' => 'Dinh huong suc khoe',
    'meal' => 'Bua an',
];
?><div class="w-full">
    <div class="mx-auto max-w-5xl py-4">
        <div class="mb-6">
            <h1 class="text-3xl font-black text-slate-900">ChA�»‰nh sA�»­a cĂ´ng thA�»©c</h1>
            <p class="mt-1 text-slate-500">CA�º­p nhA�º­t A�‘A�º§y A�‘A�»§ thĂ´ng tin, nguyĂªn liA�»‡u vĂ  cĂ¡c bA�°A�»›c thA�»±c hiA�»‡n.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= URLROOT; ?>/recipes/<?= (int) ($recipe['id'] ?? 0); ?>/edit" enctype="multipart/form-data" class="space-y-8">
            <?= csrf_field(); ?>
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="mb-5 text-xl font-bold">ThĂ´ng tin cA�¡ bA�º£n</h2>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">TiĂªu A�‘A�»</label>
                        <input class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-primary focus:ring-primary" type="text" name="title" value="<?= htmlspecialchars((string) ($recipe['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Danh mA�»¥c</label>
                        <select class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-primary focus:ring-primary" name="category_id">
                            <option value="">-- ChA�»n danh mA�»¥c --</option>
                            <?php foreach (($categories ?? []) as $category): ?>
                                <option value="<?= (int) $category['id']; ?>" <?= ((int) ($recipe['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">A�A�»™ khĂ³</label>
                        <select class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-primary focus:ring-primary" name="difficulty">
                            <option value="easy" <?= (($recipe['difficulty'] ?? 'easy') === 'easy') ? 'selected' : ''; ?>>DA�»…</option>
                            <option value="medium" <?= (($recipe['difficulty'] ?? '') === 'medium') ? 'selected' : ''; ?>>Trung bÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'¬nh</option>
                            <option value="hard" <?= (($recipe['difficulty'] ?? '') === 'hard') ? 'selected' : ''; ?>>KhÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'³</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Tags mÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'³n Ă"â€šĂ¢â'¬ÂžÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'žÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă' Ă"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä'Â¢Ă¢â'¬ÂžĂ'¢n</label>
                        <?php if ($tagsByType === []): ?>
                            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">
                                ChA�°a cĂ³ dA�»¯ liA�»‡u tags. HĂ£y chA�º¡y migration tags trA�°A�»›c.
                            </p>
                        <?php else: ?>
                            <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                                <?php foreach ($tagsByType as $type => $tagRows): ?>
                                    <div>
                                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <?= htmlspecialchars((string) ($tagTypeLabels[$type] ?? $type), ENT_QUOTES, 'UTF-8'); ?>
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ((array) $tagRows as $tag): ?>
                                                <?php $tagId = (int) ($tag['id'] ?? 0); ?>
                                                <?php if ($tagId <= 0) { continue; } ?>
                                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="tag_ids[]" value="<?= $tagId; ?>" <?= in_array($tagId, $selectedTagIds, true) ? 'checked' : ''; ?>>
                                                    <span><?= htmlspecialchars((string) ($tag['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">ThA�»i gian nA�º¥u (phĂºt)</label>
                        <input class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-primary focus:ring-primary" type="number" min="1" name="cooking_time" value="<?= htmlspecialchars((string) ($recipe['cooking_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">A�º¢nh mĂ³n A�ƒn (thay mA�»›i nA�º¿u cA�º§n)</label>
                        <input class="w-full rounded-xl border border-slate-200 px-4 py-3 file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white" type="file" name="image" accept="image/*">
                    </div>

                    <?php if (!empty($recipe['image'])): ?>
                        <div class="md:col-span-2">
                            <p class="mb-2 text-sm font-semibold text-slate-700">A�º¢nh hiA�»‡n tA�º¡i</p>
                            <img class="h-40 w-full rounded-xl object-cover md:w-80" src="<?= URLROOT; ?>/uploads/<?= htmlspecialchars((string) $recipe['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Current image">
                        </div>
                    <?php endif; ?>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">MĂ´ tA�º£</label>
                        <textarea class="min-h-[160px] w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-primary focus:ring-primary" name="description" required><?= htmlspecialchars((string) ($recipe['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold">Nguyên liệu</h2>
                    <button id="add-ingredient" class="rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white" type="button">+ ThĂªm nguyĂªn liA�»‡u</button>
                </div>

                <div id="ingredient-list" class="space-y-3">
                    <?php if (!empty($ingredients)): ?>
                        <?php foreach ($ingredients as $item): ?>
                            <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-12">
                                <div class="md:col-span-6">
                                    <label class="mb-1 block text-xs font-semibold text-slate-500">TĂªn nguyĂªn liA�»‡u</label>
                                    <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_name[]" value="<?= htmlspecialchars((string) ($item['ingredient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="mb-1 block text-xs font-semibold text-slate-500">SA�»‘ lA�°A�»£ng</label>
                                    <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_quantity[]" value="<?= htmlspecialchars((string) ($item['quantity'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-xs font-semibold text-slate-500">Đơn vị</label>
                                    <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_unit[]" value="<?= htmlspecialchars((string) ($item['unit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="md:col-span-1 flex items-end">
                                    <button class="remove-ingredient w-full rounded-lg border border-rose-300 px-2 py-2 text-sm text-rose-600 hover:bg-rose-50" type="button">XÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'³a</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-12">
                            <div class="md:col-span-6">
                                <label class="mb-1 block text-xs font-semibold text-slate-500">TĂªn nguyĂªn liA�»‡u</label>
                                <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_name[]" placeholder="VĂ­ dA�»¥: ThA�»‹t gĂ ">
                            </div>
                            <div class="md:col-span-3">
                                <label class="mb-1 block text-xs font-semibold text-slate-500">SA�»‘ lA�°A�»£ng</label>
                                <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_quantity[]" placeholder="1">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-500">Đơn vị</label>
                                <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_unit[]" placeholder="kg">
                            </div>
                            <div class="md:col-span-1 flex items-end">
                                <button class="remove-ingredient w-full rounded-lg border border-rose-300 px-2 py-2 text-sm text-rose-600 hover:bg-rose-50" type="button">XÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'³a</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold">CĂ¡c bA�°A�»›c thA�»±c hiA�»‡n</h2>
                    <button id="add-step" class="rounded-lg border border-primary px-3 py-2 text-sm font-semibold text-primary hover:bg-primary hover:text-white" type="button">+ ThĂªm bA�°A�»›c</button>
                </div>

                <div id="step-list" class="space-y-4">
                    <?php if (!empty($steps)): ?>
                        <?php foreach ($steps as $step): ?>
                            <div class="step-item rounded-xl border border-slate-200 p-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <p class="step-label text-sm font-bold text-slate-700">BA�°A�»›c <?= (int) ($step['step_number'] ?? 1); ?></p>
                                    <button class="remove-step rounded-lg border border-rose-300 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50" type="button">XÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'³a</button>
                                </div>
                                <div class="space-y-3">
                                    <textarea class="min-h-[100px] w-full rounded-lg border border-slate-200 px-3 py-2" name="step_content[]"><?= htmlspecialchars((string) ($step['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    <input type="hidden" name="step_existing_image[]" value="<?= htmlspecialchars((string) ($step['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php if (!empty($step['image'])): ?>
                                        <img class="h-36 w-full rounded-lg object-cover md:w-72" src="<?= URLROOT; ?>/uploads/<?= htmlspecialchars((string) $step['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Step image">
                                    <?php endif; ?>
                                    <input class="w-full rounded-lg border border-slate-200 px-3 py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white" type="file" name="step_images[]" accept="image/*">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="step-item rounded-xl border border-slate-200 p-4">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="step-label text-sm font-bold text-slate-700">BA�°A�»›c 1</p>
                                <button class="remove-step rounded-lg border border-rose-300 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50" type="button">XÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'³a</button>
                            </div>
                            <div class="space-y-3">
                                <textarea class="min-h-[100px] w-full rounded-lg border border-slate-200 px-3 py-2" name="step_content[]" placeholder="MĂ´ tA�º£ bA�°A�»›c thA�»±c hiA�»‡n..."></textarea>
                                <input type="hidden" name="step_existing_image[]" value="">
                                <input class="w-full rounded-lg border border-slate-200 px-3 py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white" type="file" name="step_images[]" accept="image/*">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-100" href="<?= URLROOT; ?>/recipes/<?= (int) ($recipe['id'] ?? 0); ?>">HA�»§y</a>
                <button class="rounded-xl bg-primary px-6 py-3 font-bold text-white hover:bg-primary/90" type="submit">LA�°u cA�º­p nhA�º­t</button>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const ingredientList = document.getElementById('ingredient-list');
    const addIngredientBtn = document.getElementById('add-ingredient');
    const stepList = document.getElementById('step-list');
    const addStepBtn = document.getElementById('add-step');

    const ingredientTemplate = () => `
        <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 p-4 md:grid-cols-12">
            <div class="md:col-span-6">
                <label class="mb-1 block text-xs font-semibold text-slate-500">TĂªn nguyĂªn liA�»‡u</label>
                <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_name[]" placeholder="VĂ­ dA�»¥: MuA�»‘i">
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-xs font-semibold text-slate-500">SA�»‘ lA�°A�»£ng</label>
                <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_quantity[]" placeholder="1">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Đơn vị</label>
                <input class="w-full rounded-lg border border-slate-200 px-3 py-2" type="text" name="ingredient_unit[]" placeholder="muA�»—ng">
            </div>
            <div class="md:col-span-1 flex items-end">
                <button class="remove-ingredient w-full rounded-lg border border-rose-300 px-2 py-2 text-sm text-rose-600 hover:bg-rose-50" type="button">XÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'³a</button>
            </div>
        </div>
    `;

    const updateStepLabels = () => {
        const labels = stepList.querySelectorAll('.step-label');
        labels.forEach((label, index) => {
            label.textContent = `BA�°A�»›c ${index + 1}`;
        });
    };

    addIngredientBtn?.addEventListener('click', () => {
        ingredientList.insertAdjacentHTML('beforeend', ingredientTemplate());
    });

    ingredientList?.addEventListener('click', (e) => {
        const target = e.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (target.classList.contains('remove-ingredient')) {
            const blocks = ingredientList.children;
            if (blocks.length <= 1) {
                return;
            }
            target.closest('.grid')?.remove();
        }
    });

    addStepBtn?.addEventListener('click', () => {
        const step = document.createElement('div');
        step.className = 'step-item rounded-xl border border-slate-200 p-4';
        step.innerHTML = `
            <div class="mb-2 flex items-center justify-between">
                <p class="step-label text-sm font-bold text-slate-700">BA�°A�»›c</p>
                <button class="remove-step rounded-lg border border-rose-300 px-2 py-1 text-xs text-rose-600 hover:bg-rose-50" type="button">XÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'žĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'¬Ä''Ă'šÄ'â€žĂ¢â'¬ÂšÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"â€šĂ¢â'¬ÂšÄ''Ă'³a</button>
            </div>
            <div class="space-y-3">
                <textarea class="min-h-[100px] w-full rounded-lg border border-slate-200 px-3 py-2" name="step_content[]" placeholder="MĂ´ tA�º£ bA�°A�»›c thA�»±c hiA�»‡n..."></textarea>
                <input type="hidden" name="step_existing_image[]" value="">
                <input class="w-full rounded-lg border border-slate-200 px-3 py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white" type="file" name="step_images[]" accept="image/*">
            </div>
        `;
        stepList.appendChild(step);
        updateStepLabels();
    });

    stepList?.addEventListener('click', (e) => {
        const target = e.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (target.classList.contains('remove-step')) {
            const steps = stepList.querySelectorAll('.step-item');
            if (steps.length <= 1) {
                return;
            }
            target.closest('.step-item')?.remove();
            updateStepLabels();
        }
    });

    updateStepLabels();
})();
</script>

