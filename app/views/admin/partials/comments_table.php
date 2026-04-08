<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-background-light text-slate-500">
        <tr>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">ID</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Loai</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Binh luan</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Tac gia</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Noi dung goc</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Bao cao</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Trang thai</th>
            <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Hanh dong</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php if (empty($comments)): ?>
            <tr>
                <td colspan="8" class="px-6 py-8 text-center text-slate-500">Chua co binh luan nao.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($comments as $item): ?>
                <?php
                $id = (int) ($item['id'] ?? 0);
                $targetId = (int) ($item['target_id'] ?? 0);
                $contentType = (string) ($item['content_type'] ?? 'recipe');
                $commentStatus = (string) ($item['status'] ?? 'visible');
                $reportCount = (int) ($item['report_count'] ?? 0);
                $targetLink = match ($contentType) {
                    'tip' => URLROOT . '/tips/' . $targetId,
                    'ingredient' => URLROOT . '/ingredients/' . $targetId,
                    default => URLROOT . '/admin/recipes/' . $targetId,
                };
                ?>
                <tr>
                    <td class="px-6 py-4 font-semibold text-slate-700">#<?= $id; ?></td>
                    <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?></td>
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
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700"><?= $reportCount; ?> report</span>
                        <?php else: ?>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($commentStatus === 'hidden'): ?>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Da an</span>
                        <?php else: ?>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Hien thi</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <?php if ($commentStatus === 'hidden'): ?>
                                <form method="post" action="<?= URLROOT; ?>/admin/comments/<?= $id; ?>/restore" data-confirm="Khoi phuc binh luan nay?">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="rounded-md border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" type="submit">Khoi phuc</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= URLROOT; ?>/admin/comments/<?= $id; ?>/hide" data-confirm="An binh luan nay?">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button class="rounded-md border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50" type="submit">An</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= URLROOT; ?>/admin/comments/<?= $id; ?>/delete" data-confirm="Xoa binh luan nay? Hanh dong nay khong the hoan tac.">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="content_type" value="<?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8'); ?>">
                                <button class="rounded-md border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Xoa</button>
                            </form>
                            <?php if ($reportCount > 0 && (int) ($item['user_id'] ?? 0) > 0): ?>
                                <details class="relative">
                                    <summary class="cursor-pointer list-none rounded-md border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Xu ly</summary>
                                    <div class="absolute right-0 z-20 mt-2 w-64 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                        <form method="post" action="<?= URLROOT; ?>/admin/users/<?= (int) ($item['user_id'] ?? 0); ?>/penalize" class="space-y-2">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="source_type" value="comment">
                                            <input type="hidden" name="source_id" value="<?= $id; ?>">
                                            <label class="block text-xs font-semibold text-slate-600">Hinh thuc xu ly</label>
                                            <select name="penalty_action" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                                <option value="warn">Canh cao</option>
                                                <option value="comment_lock_3">Khoa binh luan 3 ngay</option>
                                                <option value="comment_lock_7">Khoa binh luan 7 ngay</option>
                                                <option value="comment_lock_permanent">Khoa binh luan vinh vien</option>
                                                <option value="ban_permanent">Ban tai khoan vinh vien</option>
                                            </select>
                                            <label class="block text-xs font-semibold text-slate-600">Ly do</label>
                                            <input type="text" name="reason" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" value="Vi pham noi dung cong dong">
                                            <button type="submit" class="w-full rounded bg-indigo-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Ap dung</button>
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
