<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-background-light text-slate-500">
        <tr>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">ID</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Loại</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Bình luận</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Tác giả</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Nội dung gốc</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Báo cáo</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Trạng thái</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Hành động</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php if (empty($comments)): ?>
            <tr>
                <td colspan="8" class="px-6 py-8 text-center text-slate-500">Chưa có bình luận nào.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($comments as $item): ?>
                <?php
                $id = (int) ($item['id'] ?? 0);
                $targetId = (int) ($item['target_id'] ?? 0);
                $contentType = (string) ($item['content_type'] ?? 'recipe');
                $commentStatus = (string) ($item['status'] ?? 'visible');
                $reportCount = (int) ($item['report_count'] ?? 0);
                $contentTypeLabel = match ($contentType) {
                    'tip' => 'Mẹo vặt',
                    'ingredient' => 'Nguyên liệu',
                    'post' => 'Bài đăng',
                    default => 'Công thức',
                };
                $targetLink = match ($contentType) {
                    'tip' => URLROOT . '/tips/' . $targetId,
                    'ingredient' => URLROOT . '/ingredients/' . $targetId,
                    'post' => URLROOT . '/posts/' . $targetId,
                    default => URLROOT . '/admin/recipes/' . $targetId,
                };
                ?>
                <tr>
                    <td class="px-6 py-4 font-semibold text-slate-700">#<?= $id; ?></td>
                    <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($contentTypeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="px-6 py-4 max-w-[360px]">
                        <div class="line-clamp-2 text-slate-700"><?= htmlspecialchars((string) ($item['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>
                    <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars((string) ($item['author_name'] ?? 'Không rõ'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="px-6 py-4 text-slate-600">
                        <a href="<?= $targetLink; ?>" class="hover:text-primary hover:underline">
                            <?= htmlspecialchars((string) ($item['target_title'] ?? 'Nội dung đã xóa'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($reportCount > 0): ?>
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700"><?= $reportCount; ?> báo cáo</span>
                        <?php else: ?>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($commentStatus === 'hidden'): ?>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Đã ẩn</span>
                        <?php else: ?>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Hiển thị</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <?php if ($commentStatus === 'hidden'): ?>
                                <form method="post" action="<?= URLROOT; ?>/admin/comments/<?= $id; ?>/restore" data-confirm="Khôi phục bình luận này?">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="rounded-md border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" type="submit">Khôi phục</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= URLROOT; ?>/admin/comments/<?= $id; ?>/hide" data-confirm="Ẩn bình luận này?">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="rounded-md border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50" type="submit">Ẩn</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= URLROOT; ?>/admin/comments/<?= $id; ?>/delete" data-confirm="Xóa bình luận này? Hành động này không thể hoàn tác.">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?>">
                                <button class="rounded-md border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Xóa</button>
                            </form>
                            <?php if ($reportCount > 0 && (int) ($item['user_id'] ?? 0) > 0): ?>
                                <details class="relative">
                                    <summary class="cursor-pointer list-none rounded-md border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Xử lý</summary>
                                    <div class="absolute right-0 z-20 mt-2 w-64 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                        <form method="post" action="<?= URLROOT; ?>/admin/users/<?= (int) ($item['user_id'] ?? 0); ?>/penalize" class="space-y-2">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="source_type" value="comment">
                                            <input type="hidden" name="source_id" value="<?= $id; ?>">
                                            <label class="block text-xs font-semibold text-slate-600">Hình thức xử lý</label>
                                            <select name="penalty_action" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                                <option value="warn">Cảnh cáo</option>
                                                <option value="comment_lock_3">Khóa bình luận 3 ngày</option>
                                                <option value="comment_lock_7">Khóa bình luận 7 ngày</option>
                                                <option value="comment_lock_permanent">Khóa bình luận vĩnh viễn</option>
                                                <option value="ban_permanent">Ban tài khoản vĩnh viễn</option>
                                            </select>
                                            <label class="block text-xs font-semibold text-slate-600">Lý do</label>
                                            <input type="text" name="reason" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" value="Vi phạm nội dung cộng đồng">
                                            <button type="submit" class="w-full rounded bg-indigo-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Áp dụng</button>
                                        </form>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
