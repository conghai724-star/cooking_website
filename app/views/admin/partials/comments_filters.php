<div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
    <form method="get" action="<?= URLROOT; ?>/admin/comments" class="flex flex-wrap items-center gap-3">
        <input
            type="text"
            name="q"
            placeholder="Tìm bình luận, tác giả, nội dung"
            class="w-72 rounded-lg border border-slate-300 px-3 py-2 text-sm"
            value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
        >
        <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="" <?= $status === '' ? 'selected' : ''; ?>>Tất cả</option>
            <option value="visible" <?= $status === 'visible' ? 'selected' : ''; ?>>Hiển thị</option>
            <option value="hidden" <?= $status === 'hidden' ? 'selected' : ''; ?>>Đã ẩn</option>
        </select>
        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="reported" value="1" <?= $reportedOnly ? 'checked' : ''; ?>>
            Chỉ hiện bình luận bị báo cáo
        </label>
        <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Lọc</button>
    </form>
</div>
