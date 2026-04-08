<?php
$rows = is_array($rows ?? null) ? $rows : [];
$filters = is_array($filters ?? null) ? $filters : [];
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));

$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$eventType = (string) ($filters['event_type'] ?? '');
$result = (string) ($filters['result'] ?? '');
$actionKey = (string) ($filters['action_key'] ?? '');
$actorId = (int) ($filters['actor_id'] ?? 0);
$from = (string) ($filters['from'] ?? '');
$to = (string) ($filters['to'] ?? '');
$q = (string) ($filters['q'] ?? '');
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Nhat ky he thong</h1>
        <p class="text-sm text-slate-500">Theo doi thao tac dang nhap, sua/xoa noi dung va hanh dong quan tri.</p>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/logs" class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Event
                <select name="event_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
                    <option value="" <?= $eventType === '' ? 'selected' : ''; ?>>Tat ca</option>
                    <option value="auth" <?= $eventType === 'auth' ? 'selected' : ''; ?>>auth</option>
                    <option value="user_action" <?= $eventType === 'user_action' ? 'selected' : ''; ?>>user_action</option>
                    <option value="content_action" <?= $eventType === 'content_action' ? 'selected' : ''; ?>>content_action</option>
                    <option value="admin_action" <?= $eventType === 'admin_action' ? 'selected' : ''; ?>>admin_action</option>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Result
                <select name="result" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
                    <option value="" <?= $result === '' ? 'selected' : ''; ?>>Tat ca</option>
                    <option value="success" <?= $result === 'success' ? 'selected' : ''; ?>>success</option>
                    <option value="failed" <?= $result === 'failed' ? 'selected' : ''; ?>>failed</option>
                    <option value="blocked" <?= $result === 'blocked' ? 'selected' : ''; ?>>blocked</option>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Action key
                <input type="text" name="action_key" value="<?= $e($actionKey); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal" placeholder="vd: admin.user.lock">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Actor ID
                <input type="number" min="0" name="actor_id" value="<?= $actorId > 0 ? $actorId : ''; ?>" class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal" placeholder="id">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                From
                <input type="date" name="from" value="<?= $e($from); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                To
                <input type="date" name="to" value="<?= $e($to); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <label class="flex min-w-[220px] flex-col gap-1 text-xs font-semibold text-slate-600">
                Keyword
                <input type="text" name="q" value="<?= $e($q); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal" placeholder="reason/meta/ip">
            </label>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Loc</button>
        </form>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="font-semibold text-slate-800">Log gan day (<?= $total; ?>)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-2 font-semibold">ID</th>
                        <th class="px-4 py-2 font-semibold">Thoi gian</th>
                        <th class="px-4 py-2 font-semibold">Event</th>
                        <th class="px-4 py-2 font-semibold">Action</th>
                        <th class="px-4 py-2 font-semibold">Actor</th>
                        <th class="px-4 py-2 font-semibold">Target</th>
                        <th class="px-4 py-2 font-semibold">Result</th>
                        <th class="px-4 py-2 font-semibold">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($rows === []): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Không có bản ghi.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $resultValue = (string) ($row['result'] ?? '');
                            $resultClass = match ($resultValue) {
                                'success' => 'bg-emerald-100 text-emerald-700',
                                'blocked' => 'bg-amber-100 text-amber-700',
                                'failed' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                            ?>
                            <tr>
                                <td class="px-4 py-2">#<?= (int) ($row['id'] ?? 0); ?></td>
                                <td class="px-4 py-2 text-slate-600"><?= $e($row['created_at'] ?? ''); ?></td>
                                <td class="px-4 py-2"><?= $e($row['event_type'] ?? ''); ?></td>
                                <td class="px-4 py-2"><?= $e($row['action_key'] ?? ''); ?></td>
                                <td class="px-4 py-2">
                                    <?= (int) ($row['actor_id'] ?? 0); ?>
                                    <?php if (!empty($row['actor_role'])): ?>
                                        <span class="text-xs text-slate-500">(<?= $e($row['actor_role']); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?= $e($row['target_type'] ?? '-'); ?>
                                    <?php if (!empty($row['target_id'])): ?>
                                        #<?= (int) $row['target_id']; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $resultClass; ?>">
                                        <?= $e($resultValue); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 max-w-[360px]">
                                    <p class="line-clamp-1"><?= $e($row['reason'] ?? ''); ?></p>
                                </td>
                            </tr>
                            <?php if (!empty($row['meta_json'])): ?>
                                <tr>
                                    <td></td>
                                    <td colspan="7" class="px-4 pb-3 text-xs text-slate-500">
                                        <code class="block whitespace-pre-wrap break-all rounded bg-slate-50 p-2"><?= $e($row['meta_json']); ?></code>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm">
                <span class="text-slate-500">Trang <?= $page; ?> / <?= $totalPages; ?></span>
                <div class="flex items-center gap-2">
                    <?php
                    $baseParams = $_GET;
                    ?>
                    <?php if ($page > 1): ?>
                        <?php $baseParams['page'] = $page - 1; ?>
                        <a class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/admin/logs?<?= http_build_query($baseParams); ?>">Prev</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <?php $baseParams['page'] = $page + 1; ?>
                        <a class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/admin/logs?<?= http_build_query($baseParams); ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
