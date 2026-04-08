<?php
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];
$categories = is_array($categories ?? null) ? $categories : [];

$success = isset($_GET['success']) && $_GET['success'] === '1';
$error = (string) ($_GET['error'] ?? '');
$errorMessage = '';
if ($error === 'missing_name') {
    $errorMessage = 'Vui l�ng nh?p t�n nguy�n li?u.';
} elseif ($error === 'save_failed') {
    $errorMessage = 'Kh�ng th? luu nguy�n li?u. Vui l�ng th? l?i.';
}
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Thu vi?n nguy�n li?u</h1>
        <p class="text-sm text-slate-500">Admin c� th? t?o, s?a, x�a. Ngu?i d�ng g?i nguy�n li?u s? ? tr?ng th�i ch? duy?t.</p>
    </div>

    <?php if ($success): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            �� th�m nguy�n li?u m?i.
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Th�m nguy�n li?u (Admin)</h3>
            <button id="toggle-ingredient-form" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600" type="button">Th�m m?i</button>
        </div>
        <div id="ingredient-form" class="hidden">
            <form class="p-6 grid grid-cols-1 gap-4 md:grid-cols-2" method="post" action="<?= URLROOT; ?>/admin/ingredients/create" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">T�n nguy�n li?u *</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="name" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Danh m?c</label>
                    <select class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="category_id">
                        <option value="">-- Ch?n danh m?c --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id']; ?>"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">M� t?</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="description" rows="3"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">C�ng d?ng</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="usage" rows="2"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">So ch?</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="preparation" rows="2"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">B?o qu?n</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="storage" rows="2"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">H�nh ?nh</label>
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
                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Luu nguy�n li?u</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Danh s�ch nguy�n li?u</h3>
        </div>
        <?php if (empty($ingredients)): ?>
            <div class="p-6 text-sm text-slate-500">Chua c� nguy�n li?u n�o.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-background-light text-slate-500">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">T�n</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">Danh m?c</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">Tr?ng th�i</th>
                        <th class="px-6 py-4 text-[11px] font-semibold uppercase tracking-wider">H�nh d?ng</th>
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
                            ? '�� duy?t'
                            : ($status === 'rejected' ? 'T? ch?i' : 'Ch? duy?t');
                        ?>
                        <tr>
                            <td class="px-6 py-4 font-semibold text-slate-900"><?= htmlspecialchars($ingredient['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($ingredient['category_name'] ?? 'Chua ph�n lo?i', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass; ?>"><?= $statusLabel; ?></span>
                                    <?php if ($status === 'rejected' && !empty($ingredient['rejection_reason'])): ?>
                                        <span class="text-xs text-rose-600">L� do: <?= htmlspecialchars((string) $ingredient['rejection_reason'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <a class="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>">Xem</a>
                                    <?php if ($status !== 'approved'): ?>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/approve">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" type="submit">Duy?t</button>
                                        </form>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/reject" class="flex items-center gap-2" onsubmit="return confirm('T? ch?i nguy�n li?u n�y?');">
                                            <?= csrf_field(); ?>
                                            <input class="w-40 rounded-md border border-slate-200 px-2 py-1 text-xs" name="reason" placeholder="L� do t? ch?i">
                                            <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">T? ch?i</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($status === 'approved'): ?>
                                        <a class="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/edit">S?a</a>
                                        <form method="post" action="<?= URLROOT; ?>/admin/ingredients/<?= (int) $ingredient['id']; ?>/delete" onsubmit="return confirm('X�a nguy�n li?u n�y?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">X�a</button>
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
