<div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <form method="get" action="<?= URLROOT; ?>/admin/comments" class="flex flex-wrap items-center gap-3">
        <input
            class="w-64 max-w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30"
            name="q"
            type="text"
            placeholder="Tim theo noi dung/tac gia"
            value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
        >
        <select name="status" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
            <option value="" <?= $status === '' ? 'selected' : ''; ?>>Tat ca</option>
            <option value="visible" <?= $status === 'visible' ? 'selected' : ''; ?>>Hien thi</option>
            <option value="hidden" <?= $status === 'hidden' ? 'selected' : ''; ?>>Da an</option>
        </select>
        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="reported" value="1" <?= $reportedOnly ? 'checked' : ''; ?>>
            Chi hien binh luan bi báo cáo
        </label>
        <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Loc</button>
    </form>
</div>
