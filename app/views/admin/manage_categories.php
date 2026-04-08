<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Quản lA� danh mục</h1>
        <p class="text-sm text-slate-500">T? ch?c nh�m m�n an v� b? suu t?p.</p>
    </div>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <span class="material-symbols-outlined text-base">category</span>
                <span>Danh sA�ch danh mục</span>
            </div>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="button">ThA�m danh mục</button>
        </div>

        <?php if (!empty($categories)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                <?php foreach ($categories as $category): ?>
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <span class="font-medium text-slate-800"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <button class="text-slate-500 hover:text-primary" type="button">
                            <span class="material-symbols-outlined text-base">edit</span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-6 text-sm text-slate-500">Kh�ng tA�m thấy danh mục nA�o.</div>
        <?php endif; ?>
    </div>
</div>
