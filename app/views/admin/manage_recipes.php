<?php
$recipes = is_array($recipes ?? null) ? $recipes : [];
$categories = is_array($categories ?? null) ? $categories : [];
$success = isset($_GET['success']) && $_GET['success'] === '1';
$error = (string) ($_GET['error'] ?? '');
$errorMessage = '';
if ($error === 'missing') {
    $errorMessage = 'Vui l�ng nh?p ti�u d? v� m� t?.';
} elseif ($error === 'save_failed') {
    $errorMessage = 'Kh�ng th? luu c�ng th?c.';
}

$pendingRecipes = [];
$rejectedRecipes = [];
$approvedRecipes = [];

foreach ($recipes as $recipe) {
    $status = (string) ($recipe['status'] ?? 'approved');
    if ($status === 'pending') {
        $pendingRecipes[] = $recipe;
    } elseif ($status === 'rejected') {
        $rejectedRecipes[] = $recipe;
    } else {
        $approvedRecipes[] = $recipe;
    }
}

$pendingCount = count($pendingRecipes) + count($rejectedRecipes);
$approvedCount = count($approvedRecipes);
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Qu?n l� c�ng th?c</h1>
        <p class="text-sm text-slate-500">Ki?m duy?t, c?p nh?t v� qu?n l� n?i dung c�ng th?c.</p>
    </div>

    <?php if ($success): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">�� th�m c�ng th?c m?i.</div>
    <?php endif; ?>
    <?php if ($errorMessage !== ''): ?>
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <span class="material-symbols-outlined text-base">restaurant_menu</span>
                <span>Danh s�ch c�ng th?c</span>
            </div>
            <div class="flex items-center gap-3">
                <button id="toggle-recipe-form" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600" type="button">Th�m m?i</button>
            </div>
        </div>

        <div id="recipe-form" class="hidden border-b border-slate-100 p-6">
            <form class="grid grid-cols-1 gap-4 md:grid-cols-2" method="post" action="<?= URLROOT; ?>/admin/recipes/create" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Ti�u d? *</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="title" required>
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
                    <label class="mb-1 block text-xs font-semibold text-slate-500">M� t? *</label>
                    <textarea class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="description" rows="3" required></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Th?i gian n?u (ph�t)</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="cooking_time" type="number" min="0">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-500">�? kh�</label>
                    <select class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" name="difficulty">
                        <option value="easy">D?</option>
                        <option value="medium">Trung b�nh</option>
                        <option value="hard">Kh�</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">H�nh ?nh</label>
                    <input class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm" type="file" name="image" accept="image/*">
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Luu c�ng th?c</button>
                </div>
            </form>
        </div>

        <div class="border-b border-slate-100 px-6 pt-4">
            <div class="flex flex-wrap gap-6 text-sm font-semibold">
                <button class="admin-tab border-b-2 pb-3 border-primary text-primary" type="button" data-target="pending-section">
                    Ch? duy?t (<?= (int) $pendingCount; ?>)
                </button>
                <button class="admin-tab border-b-2 pb-3 border-transparent text-slate-500 hover:text-primary" type="button" data-target="approved-section">
                    �� duy?t (<?= (int) $approvedCount; ?>)
                </button>
            </div>
        </div>

        <div id="pending-section" class="admin-section">
            <?php if ($pendingCount === 0): ?>
                <div class="p-6 text-sm text-slate-500">Kh�ng c� c�ng th?c n�o dang ch? duy?t.</div>
            <?php else: ?>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-background-light text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">ID</th>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Ti�u d?</th>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">T�c gi?</th>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Tr?ng th�i</th>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">H�nh d?ng</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <?php foreach (array_merge($pendingRecipes, $rejectedRecipes) as $recipe): ?>
                            <?php
                            $status = $recipe['status'] ?? 'approved';
                            $statusClass = $status === 'approved'
                                ? 'bg-emerald-100 text-emerald-700'
                                : ($status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-yellow-100 text-yellow-700');
                            $statusLabel = $status === 'approved'
                                ? '�� duy?t'
                                : ($status === 'rejected' ? 'T? ch?i' : 'Ch? duy?t');
                            ?>
                            <tr>
                                <td class="px-6 py-4 text-slate-500"><?= (int) $recipe['id']; ?></td>
                                <td class="px-6 py-4 font-medium text-slate-900"><?= htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($recipe['author_name'] ?? 'Kh�ng r�', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusClass; ?>"><?= $statusLabel; ?></span></td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a class="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/admin/recipes/<?= (int) $recipe['id']; ?>">Xem</a>
                                        <?php if ($status !== 'approved'): ?>
                                            <form method="post" action="<?= URLROOT; ?>/admin/recipes/<?= (int) $recipe['id']; ?>/approve">
                                                <?= csrf_field(); ?>
                                                <button class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" type="submit">Duy?t</button>
                                            </form>
                                            <form method="post" action="<?= URLROOT; ?>/admin/recipes/<?= (int) $recipe['id']; ?>/reject" onsubmit="return confirm('T? ch?i c�ng th?c n�y?');">
                                                <?= csrf_field(); ?>
                                                <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">T? ch?i</button>
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

        <div id="approved-section" class="admin-section hidden">
            <?php if ($approvedCount === 0): ?>
                <div class="p-6 text-sm text-slate-500">Chua c� c�ng th?c d� duy?t.</div>
            <?php else: ?>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-background-light text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">ID</th>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Ti�u d?</th>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">T�c gi?</th>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Tr?ng th�i</th>
                            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">H�nh d?ng</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        <?php foreach ($approvedRecipes as $recipe): ?>
                            <tr>
                                <td class="px-6 py-4 text-slate-500"><?= (int) $recipe['id']; ?></td>
                                <td class="px-6 py-4 font-medium text-slate-900"><?= htmlspecialchars($recipe['title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($recipe['author_name'] ?? 'Kh�ng r�', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-6 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700">�� duy?t</span></td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a class="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600" href="<?= URLROOT; ?>/admin/recipes/<?= (int) $recipe['id']; ?>">Xem</a>
                                        <form method="post" action="<?= URLROOT; ?>/admin/recipes/<?= (int) $recipe['id']; ?>/delete" onsubmit="return confirm('X�a c�ng th?c d� duy?t n�y?');">
                                            <?= csrf_field(); ?>
                                            <button class="rounded-md border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700" type="submit">X�a</button>
                                        </form>
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
</div>

<script>
    (function () {
        var toggle = document.getElementById('toggle-recipe-form');
        var form = document.getElementById('recipe-form');
        if (toggle && form) {
            toggle.addEventListener('click', function () {
                form.classList.toggle('hidden');
            });
        }

        var tabs = document.querySelectorAll('.admin-tab');
        var sections = document.querySelectorAll('.admin-section');

        function activateTab(targetId) {
            sections.forEach(function (section) {
                section.classList.toggle('hidden', section.id !== targetId);
            });
            tabs.forEach(function (tab) {
                var isActive = tab.getAttribute('data-target') === targetId;
                tab.classList.toggle('border-primary', isActive);
                tab.classList.toggle('text-primary', isActive);
                tab.classList.toggle('border-transparent', !isActive);
                tab.classList.toggle('text-slate-500', !isActive);
            });
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activateTab(tab.getAttribute('data-target'));
            });
        });

        if (tabs.length > 0) {
            activateTab(tabs[0].getAttribute('data-target'));
        }
    })();
</script>
