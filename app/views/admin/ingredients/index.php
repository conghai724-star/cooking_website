<?php
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];
$categories = is_array($categories ?? null) ? $categories : [];

$success = isset($_GET['success']) && $_GET['success'] === '1';
$error = (string) ($_GET['error'] ?? '');
$errorMessage = '';
if ($error === 'missing_name') {
    $errorMessage = 'Vui l\u00f2ng nh\u1eadp t\u00ean nguy\u00ean li\u1ec7u.';
} elseif ($error === 'save_failed') {
    $errorMessage = 'Kh\u00f4ng th\u1ec3 l\u01b0u nguy\u00ean li\u1ec7u. Vui l\u00f2ng th\u1eed l\u1ea1i.';
}
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Th\u01b0 vi\u1ec7n nguy\u00ean li\u1ec7u</h1>
        <p class="text-sm text-slate-500">Admin c\u00f3 th\u1ec3 t\u1ea1o, s\u1eda, x\u00f3a. Ng\u01b0\u1eddi d\u00f9ng g\u1eedi nguy\u00ean li\u1ec7u s\u1ebd \u1ở tr\u1ea1ng th\u00e1i ch\u1edd duy\u1ec7t.</p>
    </div>

    <?php if ($success): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            A�¿½A? thA?m nguyA?n li?u m?i.
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Th\u00eam nguy\u00ean li\u1ec7u (Admin)</h3>
            <button id="toggle-ingredient-form" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600" type="button">Th\u00eam m\u1edbi</button>
        </div>
        <div id="ingredient-form" class="hidden">
            <form class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2" method="post" action="<?= URLROOT; ?>/admin/ingredients/create" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">T\u00ean nguy\u00ean li\u1ec7u *</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="name" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Danh m\u1ee5c</label>
                    <select class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="category_id">
                        <option value="">-- Ch\u1ecdn danh m\u1ee5c --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id']; ?>"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">M\u00f4 t\u1ea3</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="description" rows="3"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">C\u00f4ng d\u1ee5ng</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="usage" rows="2"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">SA�¡ chA�º¿</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="preparation" rows="2"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">BA�º£o quA�º£n</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="storage" rows="2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">HĂ¬nh A�º£nh</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="file" name="image" accept="image/*">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Calories (kcal)</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="calories">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Protein (g)</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="protein">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Fat (g)</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="fat">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Carb (g)</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="carb">
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">L\u01b0u nguy\u00ean li\u1ec7u</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Danh s\u00e1ch nguy\u00ean li\u1ec7u</h3>
        </div>
        <?php if (empty($ingredients)): ?>
            <div class="p-6 text-sm text-slate-500">ChA�°a cĂ³ nguyĂªn liA�»‡u nĂ o.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-background-light text-slate-500">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">TÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Âªn</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">Danh mA�»¥c</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">TrA�º¡ng thĂ¡i</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">HÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â nh Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‹Å“Ă„â€Ă‚Â¡Ä‚â€Ă‚Â»Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¢ng</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach ($ingredients as $ingredient): ?>
                        <?php
                        $status = $ingredient['status'] ?? 'approved';
                        $statusClass = $status === 'approved'
                            ? 'bg-emerald-100 text-emerald-700'
                            : ($status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-yellow-100 text-yellow-700');
                        $statusLabel = $status === 'approved'
                            ? 'ĐA� duyệt'
                            : ($status === 'rejected' ? 'TA�»« chA�»‘i' : 'ChA�» duyA�»‡t');
                        ?>
                        <tr>
                            <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($ingredient['category_name'] ?? 'ChA�°a phĂ¢n loA�º¡i', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass; ?>"><?= $statusLabel; ?></span>
                                    <?php if ($status === 'rejected' && !empty($ingredient['rejection_reason'])): ?>
                                        <span class="text-xs text-rose-600">LÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â½ do: <?= htmlspecialchars((string) $ingredient['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a class="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>">Xem</a>
                                    <?php if ($status !== 'approved'): ?>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/approve">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" type="submit">DuyA�»‡t</button>
                                        </form>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/reject" class="flex items-center gap-2" onsubmit="return confirm('TA�»« chA�»‘i nguyĂªn liA�»‡u nĂ y?');">
                                            <?= csrf_field(); ?>
                                            <input class="w-40 rounded-md border border-slate-200 px-2 py-1 text-xs" name="reason" placeholder="LĂ½ do tA�»« chA�»‘i">
                                            <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">TA�»« chA�»‘i</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status === 'approved'): ?>
                                        <a class="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/edit">SA�»­a</a>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/delete" onsubmit="return confirm('XĂ³a nguyĂªn liA�»‡u nĂ y?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">XÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â³a</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        var toggle = document.getElementById('toggle-ingredient-form');
        var form = document.getElementById('ingredient-form');
        if (!toggle || !form) {
            return;
        }
        toggle.addEventListener('click', function () {
            form.classList.toggle('hidden');
        });
    })();
</script>


