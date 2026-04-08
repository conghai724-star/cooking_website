<?php
$set = is_array($set ?? null) ? $set : [];
$participants = is_array($participants ?? null) ? $participants : [];
$passers = is_array($passers ?? null) ? $passers : [];
$noticeText = (string) ($noticeText ?? '');
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Chi tiA�º¿t ngA�°A�»i lĂ m quiz</h1>
            <p class="text-sm text-slate-500"><?= htmlspecialchars((string) ($set['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= URLROOT; ?>/admin/quizzes/<?= (int) ($set['id'] ?? 0); ?>" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">CA�º­p nhA�º­t cĂ¢u hA�»i</a>
            <a href="<?= URLROOT; ?>/admin/quizzes" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Quay lA�º¡i</a>
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
                <h2 class="text-base font-semibold text-slate-900">Danh sĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡ch Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€¹Ă…â€œĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â£ tham gia</h2>
                <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700"><?= count($participants); ?> ngA�°A�»i</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2 pr-3">NgA�°A�»i dĂ¹ng</th>
                        <th class="py-2 pr-3">LA�º§n lĂ m</th>
                        <th class="py-2 pr-3">Điểm cao nhất</th>
                        <th class="py-2 pr-3">LA�º§n gA�º§n nhA�º¥t</th>
                        <th class="py-2">TrA�º¡ng thĂ¡i</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($participants === []): ?>
                        <tr>
                            <td colspan="5" class="py-3 text-slate-500">ChA�°a cĂ³ ngA�°A�»i tham gia.</td>
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
                                        <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">A�Ă£ A�‘A�º¡t</span>
                                    <?php elseif ($hasPassed): ?>
                                        <span class="rounded bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â£ qua bĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â i</span>
                                    <?php else: ?>
                                        <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">ChA�°a A�‘A�º¡t</span>
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
                <h2 class="text-base font-semibold text-slate-900">Danh sĂ¡ch A�‘Ă£ A�‘A�º¡t chA�»©ng chA�»‰</h2>
                <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700"><?= count($passers); ?> ngA�°A�»i</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2 pr-3">NgA�°A�»i dĂ¹ng</th>
                        <th class="py-2 pr-3">Điểm</th>
                        <th class="py-2 pr-3">Uy tĂ­n cA�»™ng</th>
                        <th class="py-2 pr-3">MĂ£ chA�»©ng chA�»‰</th>
                        <th class="py-2">NgĂ y cA�º¥p</th>
                        <th class="py-2 text-right">Thao tĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡c</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($passers === []): ?>
                        <tr>
                            <td colspan="6" class="py-3 text-slate-500">ChA�°a cĂ³ ngA�°A�»i A�‘A�º¡t chA�»©ng chA�»‰.</td>
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
                                    <form method="post" action="<?= URLROOT; ?>/admin/quiz-certificates/<?= (int) ($passer['certificate_id'] ?? 0); ?>/delete" onsubmit="return confirm('BA�º¡n cĂ³ chA�º¯c muA�»‘n xĂ³a chA�»©ng nhA�º­n nĂ y?');">
                                        <?= csrf_field(); ?>
                                        <input type="hidden" name="set_id" value="<?= (int) ($set['id'] ?? 0); ?>">
                                        <button type="submit" class="rounded border border-rose-300 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50">XĂ³a chA�»©ng nhA�º­n</button>
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

