<?php
$rows = is_array($rows ?? null) ? $rows : [];
$states = is_array($states ?? null) ? $states : [];
$filters = is_array($filters ?? null) ? $filters : [];
$intentOptions = is_array($intentOptions ?? null) ? $intentOptions : [];
$codeOptions = is_array($codeOptions ?? null) ? $codeOptions : [];
$page = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$total = max(0, (int) ($total ?? 0));

$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$userId = (int) ($filters['user_id'] ?? 0);
$intent = (string) ($filters['intent'] ?? '');
$code = (string) ($filters['code'] ?? '');
$from = (string) ($filters['from'] ?? '');
$to = (string) ($filters['to'] ?? '');
$q = (string) ($filters['q'] ?? '');
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Lịch sử chat</h1>
        <p class="text-sm text-slate-500">Xem tin nhắn, intent match và state hội thoại của người dùng.</p>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/chat-histories" class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                User ID
                <input type="number" min="0" name="user_id" value="<?= $userId > 0 ? $userId : ''; ?>" class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Intent
                <select name="intent" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
                    <option value="">Tất cả</option>
                    <?php foreach ($intentOptions as $opt): ?>
                        <option value="<?= $e($opt); ?>" <?= $intent === $opt ? 'selected' : ''; ?>><?= $e($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Code
                <select name="code" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
                    <option value="">Tất cả</option>
                    <?php foreach ($codeOptions as $opt): ?>
                        <option value="<?= $e($opt); ?>" <?= $code === $opt ? 'selected' : ''; ?>><?= $e($opt); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Từ ngày
                <input type="date" name="from" value="<?= $e($from); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Đến ngày
                <input type="date" name="to" value="<?= $e($to); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <label class="flex min-w-[240px] flex-col gap-1 text-xs font-semibold text-slate-600">
                Từ khóa
                <input type="text" name="q" value="<?= $e($q); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal" placeholder="tin nhắn/meta">
            </label>
            <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">Lọc</button>
        </form>
    </div>

    <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
        <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h3 class="font-semibold text-slate-800">Bản ghi chat (<?= $total; ?>)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-2 font-semibold">ID</th>
                            <th class="px-4 py-2 font-semibold">Thời gian</th>
                            <th class="px-4 py-2 font-semibold">User</th>
                            <th class="px-4 py-2 font-semibold">Intent</th>
                            <th class="px-4 py-2 font-semibold">Code</th>
                            <th class="px-4 py-2 font-semibold">Q/A</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($rows === []): ?>
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Không có bản ghi.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="px-4 py-2">#<?= (int) ($row['id'] ?? 0); ?></td>
                                    <td class="px-4 py-2 text-slate-600"><?= $e($row['created_at'] ?? ''); ?></td>
                                    <td class="px-4 py-2"><?= (int) ($row['user_id'] ?? 0); ?></td>
                                    <td class="px-4 py-2"><?= $e($row['matched_intent'] ?? ''); ?></td>
                                    <td class="px-4 py-2"><?= $e($row['result_code'] ?? ''); ?></td>
                                    <td class="px-4 py-2 max-w-[520px]">
                                        <p><span class="font-semibold text-slate-700">U:</span> <?= $e($row['user_message'] ?? ''); ?></p>
                                        <p class="mt-1 text-slate-600"><span class="font-semibold text-slate-700">B:</span> <?= $e($row['bot_message'] ?? ''); ?></p>
                                        <?php if (!empty($row['meta_json'])): ?>
                                            <details class="mt-1">
                                                <summary class="cursor-pointer text-xs text-slate-500">meta</summary>
                                                <code class="block whitespace-pre-wrap break-all rounded bg-slate-50 p-2 text-xs"><?= $e($row['meta_json']); ?></code>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3 text-sm">
                    <span class="text-slate-500">Trang <?= $page; ?> / <?= $totalPages; ?></span>
                    <div class="flex items-center gap-2">
                        <?php $baseParams = $_GET; ?>
                        <?php if ($page > 1): ?>
                            <?php $baseParams['page'] = $page - 1; ?>
                            <a class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/admin/chat-histories?<?= http_build_query($baseParams); ?>">Trước</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <?php $baseParams['page'] = $page + 1; ?>
                            <a class="rounded border border-slate-300 px-3 py-1 text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/admin/chat-histories?<?= http_build_query($baseParams); ?>">Tiếp</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="font-semibold text-slate-800">Trạng thái gần nhất</h3>
            </div>
            <div class="divide-y divide-slate-100">
                <?php if ($states === []): ?>
                    <div class="px-4 py-6 text-sm text-slate-500">Chưa có dữ liệu trạng thái.</div>
                <?php else: ?>
                    <?php foreach ($states as $state): ?>
                        <div class="px-4 py-3 text-sm">
                            <div class="font-semibold text-slate-800"><?= $e($state['user_name'] ?? ''); ?> (#<?= (int) ($state['user_id'] ?? 0); ?>)</div>
                            <div class="mt-1 text-slate-600">meal: <span class="font-medium"><?= $e(($state['chat_state']['meal'] ?? null) ?? '-'); ?></span></div>
                            <div class="text-slate-600">calories: <span class="font-medium"><?= $e(($state['chat_state']['calories'] ?? null) ?? '-'); ?></span></div>
                            <div class="text-slate-600">allergies: <span class="font-medium"><?= $e(implode(', ', (array) ($state['chat_state']['allergies'] ?? []))); ?></span></div>
                            <div class="mt-1 text-xs text-slate-400"><?= $e($state['updated_at'] ?? ''); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

