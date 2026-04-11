<?php
$set = is_array($set ?? null) ? $set : [];
$participants = is_array($participants ?? null) ? $participants : [];
$passers = is_array($passers ?? null) ? $passers : [];
$noticeText = (string) ($noticeText ?? '');
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Chi tiết người làm quiz</h1>
            <p class="text-sm text-slate-500"><?= htmlspecialchars((string) ($set['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= URLROOT; ?>/admin/quizzes/<?= (int) ($set['id'] ?? 0); ?>" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cập nhật câu hỏi</a>
            <a href="<?= URLROOT; ?>/admin/quizzes" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Quay lại</a>
        </div>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Danh sách tham gia</h2>
                <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700"><?= count($participants); ?> người</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2 pr-3">Người dùng</th>
                        <th class="py-2 pr-3">Lần làm</th>
                        <th class="py-2 pr-3">Điểm cao nhất</th>
                        <th class="py-2 pr-3">Lần gần nhất</th>
                        <th class="py-2">Trạng thái</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($participants === []): ?>
                        <tr>
                            <td colspan="5" class="py-3 text-slate-500">Chưa có người tham gia.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($participants as $participant): ?>
                            <?php
                            $name = trim((string) ($participant['user_name'] ?? ''));
                            $email = trim((string) ($participant['user_email'] ?? ''));
                            $label = $name !== '' ? $name : ($email !== '' ? $email : ('User #' . (int) ($participant['user_id'] ?? 0)));
                            $hasPassed = (int) ($participant['has_passed'] ?? 0) === 1;
                            $hasCertificate = trim((string) ($participant['certificate_code'] ?? '')) !== '';
                            ?>
                            <tr class="border-t border-slate-100">
                                <td class="py-2 pr-3">
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php if ($email !== '' && $email !== $label): ?>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 pr-3 text-slate-700"><?= (int) ($participant['attempts_count'] ?? 0); ?></td>
                                <td class="py-2 pr-3 text-slate-700"><?= number_format((float) ($participant['best_score_percent'] ?? 0), 2); ?>%</td>
                                <td class="py-2 pr-3 text-slate-700"><?= htmlspecialchars((string) ($participant['last_attempt_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="py-2 text-slate-700">
                                    <?php if ($hasCertificate): ?>
                                        <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Đã đạt</span>
                                    <?php elseif ($hasPassed): ?>
                                        <span class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Đã qua bài</span>
                                    <?php else: ?>
                                        <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">Chưa đạt</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Danh sách đã đạt chứng chỉ</h2>
                <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700"><?= count($passers); ?> người</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2 pr-3">Người dùng</th>
                        <th class="py-2 pr-3">Điểm</th>
                        <th class="py-2 pr-3">Uy tín cộng</th>
                        <th class="py-2 pr-3">Mã chứng chỉ</th>
                        <th class="py-2">Ngày cấp</th>
                        <th class="py-2 text-right">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($passers === []): ?>
                        <tr>
                            <td colspan="6" class="py-3 text-slate-500">Chưa có người đạt chứng chỉ.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($passers as $passer): ?>
                            <?php
                            $name = trim((string) ($passer['user_name'] ?? ''));
                            $email = trim((string) ($passer['user_email'] ?? ''));
                            $label = $name !== '' ? $name : ($email !== '' ? $email : ('User #' . (int) ($passer['user_id'] ?? 0)));
                            ?>
                            <tr class="border-t border-slate-100">
                                <td class="py-2 pr-3">
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php if ($email !== '' && $email !== $label): ?>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 pr-3 text-slate-700"><?= number_format((float) ($passer['score_percent'] ?? 0), 2); ?>%</td>
                                <td class="py-2 pr-3 text-slate-700">+<?= (int) ($passer['awarded_reputation_points'] ?? 0); ?></td>
                                <td class="py-2 pr-3 text-slate-700"><?= htmlspecialchars((string) ($passer['certificate_code'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="py-2 text-slate-700"><?= htmlspecialchars((string) ($passer['awarded_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="py-2 text-right">
                                    <form method="post" action="<?= URLROOT; ?>/admin/quiz-certificates/<?= (int) ($passer['certificate_id'] ?? 0); ?>/delete" onsubmit="return confirm('Bạn có chắc muốn xóa chứng nhận này?');">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="set_id" value="<?= (int) ($set['id'] ?? 0); ?>">
                                        <button type="submit" class="rounded border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa chứng nhận</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

