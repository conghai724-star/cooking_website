<?php
$keyword = (string) ($keyword ?? '');
$state = (string) ($state ?? 'all');
$page = (int) ($page ?? 1);
$totalPages = (int) ($totalPages ?? 1);
$total = (int) ($total ?? 0);
$notice = (string) ($notice ?? '');
$roleNames = is_array($roleNames ?? null) ? $roleNames : ['user', 'super_admin', 'mod', 'support'];
$canManageRoles = (bool) ($canManageRoles ?? false);
$currentAdminId = (int) (current_admin()['id'] ?? 0);

$buildUrl = static function (int $targetPage) use ($keyword, $state): string {
    $params = ['page' => max(1, $targetPage)];
    if ($keyword !== '') {
        $params['q'] = $keyword;
    }
    if ($state !== 'all') {
        $params['state'] = $state;
    }
    return URLROOT . '/admin/users?' . http_build_query($params);
};

$noticeMap = [
    'banned' => '�� ban t�i kho?n.',
    'unbanned' => '�� m? ban t�i kho?n.',
    'deleted' => '�� x�a m?m t�i kho?n.',
    'restored' => '�� kh�i ph?c t�i kho?n.',
    'admin_created' => '�� t?o t�i kho?n admin th�nh c�ng.',
    'admin_create_failed' => 'Kh�ng th? t?o t�i kho?n admin.',
    'role_updated' => '�� c?p nh?t vai tr� ngu?i d�ng.',
    'role_update_failed' => 'Kh�ng th? c?p nh?t vai tr� ngu?i d�ng.',
];
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Qu?n l� ngu?i d�ng</h1>
        <p class="text-sm text-slate-500">Ban, x�a m?m, kh�i ph?c t�i kho?n k�m t�m ki?m, l?c v� ph�n trang.</p>
    </div>

    <?php if ($notice !== ''): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <?= htmlspecialchars($noticeMap[$notice] ?? 'Thao t�c th�nh c�ng.', ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if ($canManageRoles): ?>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-sm font-semibold text-slate-800">C?p t�i kho?n admin v� ph�n quy?n</h2>
            <form method="post" action="<?= URLROOT; ?>/admin/users/create-admin" class="grid gap-3 md:grid-cols-4">
                <?= csrf_field(); ?>
                <input name="name" required type="text" placeholder="H? t�n" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input name="email" required type="email" placeholder="Email" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <input name="password" required minlength="6" type="password" placeholder="M?t kh?u (>=6 k� t?)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <div class="flex gap-2">
                    <select name="role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <?php foreach ($roleNames as $roleName): ?>
                            <?php if ($roleName === 'user') { continue; } ?>
                            <option value="<?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">T?o</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <form method="get" action="<?= URLROOT; ?>/admin/users" class="flex flex-wrap items-center gap-3">
                <input
                    class="w-72 max-w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30"
                    name="q"
                    type="text"
                    placeholder="T�m theo t�n/email"
                    value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                >
                <select class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-primary focus:ring-2 focus:ring-primary/30" name="state">
                    <option value="all" <?= $state === 'all' ? 'selected' : ''; ?>>T?t c? tr?ng th�i</option>
                    <option value="active" <?= $state === 'active' ? 'selected' : ''; ?>>Ho?t d?ng</option>
                    <option value="banned" <?= $state === 'banned' ? 'selected' : ''; ?>>B? ban</option>
                    <option value="deleted" <?= $state === 'deleted' ? 'selected' : ''; ?>>�� x�a m?m</option>
                </select>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" type="submit">L?c</button>
            </form>
        </div>

        <?php if (!empty($users)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-background-light text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">ID</th>
                        <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">T�n</th>
                        <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Email</th>
                        <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Vai tr�</th>
                        <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Tr?ng th�i</th>
                        <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">H�nh d?ng</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $user): ?>
                        <?php
                        $uid = (int) ($user['id'] ?? 0);
                        $role = (string) ($user['role'] ?? 'user');
                        $accountState = (string) ($user['account_state'] ?? 'active');
                        $isSelf = $uid === $currentAdminId;
                        ?>
                        <tr>
                            <td class="px-6 py-4 text-slate-500"><?= $uid; ?></td>
                            <td class="px-6 py-4 font-medium text-slate-900"><?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    <?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if ($canManageRoles && !$isSelf && $accountState !== 'deleted'): ?>
                                    <form method="post" action="<?= URLROOT; ?>/admin/users/<?= $uid; ?>/role" class="mt-2 flex items-center gap-2">
                                        <?= csrf_field(); ?>
                                        <select name="role" class="rounded border border-slate-300 px-2 py-1 text-xs">
                                            <?php foreach ($roleNames as $roleName): ?>
                                                <option value="<?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>" <?= $roleName === $role ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">�?i</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $stateClasses = 'bg-slate-100 text-slate-700';
                                if ($accountState === 'active') {
                                    $stateClasses = 'bg-emerald-100 text-emerald-700';
                                } elseif ($accountState === 'banned') {
                                    $stateClasses = 'bg-amber-100 text-amber-700';
                                } elseif ($accountState === 'deleted') {
                                    $stateClasses = 'bg-rose-100 text-rose-700';
                                }
                                ?>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $stateClasses; ?>">
                                    <?= htmlspecialchars($accountState, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if ($accountState === 'banned'): ?>
                                    <div class="mt-1 text-xs text-slate-500">
                                        <?php if (!empty($user['banned_until'])): ?>
                                            �?n: <?= htmlspecialchars((string) $user['banned_until'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php else: ?>
                                            Vinh vi?n
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($user['ban_reason'])): ?>
                                        <div class="mt-1 text-xs text-slate-500">
                                            L� do: <?= htmlspecialchars((string) $user['ban_reason'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <?php if ($role === 'user' && !$isSelf): ?>
                                        <?php if ($accountState === 'active'): ?>
                                            <details class="relative">
                                                <summary class="list-none cursor-pointer rounded border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50">Ban</summary>
                                                <div class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                                    <form method="post" action="<?= URLROOT; ?>/admin/users/<?= $uid; ?>/ban" class="space-y-2" onsubmit="return confirm('X�c nh?n ban t�i kho?n n�y?');">
                                                        <?= csrf_field(); ?>
                                                        <label class="block text-xs font-semibold text-slate-600">L� do ban</label>
                                                        <textarea name="ban_reason" rows="2" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" placeholder="Nh?p l� do..."></textarea>
                                                        <label class="block text-xs font-semibold text-slate-600">Th?i gian ban</label>
                                                        <select name="ban_days" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">
                                                            <option value="1">1 ng�y</option>
                                                            <option value="3">3 ng�y</option>
                                                            <option value="7" selected>7 ng�y</option>
                                                            <option value="30">30 ng�y</option>
                                                            <option value="0">Vinh vi?n</option>
                                                        </select>
                                                        <button class="rounded bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600" type="submit">X�c nh?n ban</button>
                                                    </form>
                                                </div>
                                            </details>
                                            <form method="post" action="<?= URLROOT; ?>/admin/users/<?= $uid; ?>/delete" onsubmit="return confirm('X�a m?m t�i kho?n n�y?');">
                                                <?= csrf_field(); ?>
                                                <button class="rounded border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">X�a</button>
                                            </form>
                                        <?php elseif ($accountState === 'banned'): ?>
                                            <form method="post" action="<?= URLROOT; ?>/admin/users/<?= $uid; ?>/unban">
                                                <?= csrf_field(); ?>
                                                <button class="rounded border border-emerald-300 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" type="submit">M? ban</button>
                                            </form>
                                            <form method="post" action="<?= URLROOT; ?>/admin/users/<?= $uid; ?>/delete" onsubmit="return confirm('X�a m?m t�i kho?n n�y?');">
                                                <?= csrf_field(); ?>
                                                <button class="rounded border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">X�a</button>
                                            </form>
                                        <?php elseif ($accountState === 'deleted'): ?>
                                            <form method="post" action="<?= URLROOT; ?>/admin/users/<?= $uid; ?>/restore">
                                                <?= csrf_field(); ?>
                                                <button class="rounded border border-sky-300 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-50" type="submit">Kh�i ph?c</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">T�i kho?n d?c quy?n</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-4 border-t border-slate-100 px-6 py-4 text-sm">
                <p class="text-slate-500">T?ng: <?= $total; ?> t�i kho?n</p>
                <div class="flex items-center gap-2">
                    <a href="<?= $page > 1 ? $buildUrl($page - 1) : '#'; ?>" class="rounded border px-3 py-1.5 <?= $page > 1 ? 'border-slate-300 text-slate-700 hover:bg-slate-50' : 'border-slate-200 text-slate-300 pointer-events-none'; ?>">Tru?c</a>
                    <span class="text-slate-600">Trang <?= $page; ?>/<?= $totalPages; ?></span>
                    <a href="<?= $page < $totalPages ? $buildUrl($page + 1) : '#'; ?>" class="rounded border px-3 py-1.5 <?= $page < $totalPages ? 'border-slate-300 text-slate-700 hover:bg-slate-50' : 'border-slate-200 text-slate-300 pointer-events-none'; ?>">Sau</a>
                </div>
            </div>
        <?php else: ?>
            <div class="p-6 text-sm text-slate-500">Kh�ng t�m th?y ngu?i d�ng.</div>
        <?php endif; ?>
    </div>
</div>
