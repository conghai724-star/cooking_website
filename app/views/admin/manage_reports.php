<?php
$status = (string) ($status ?? '');
$type = (string) ($type ?? '');
$keyword = (string) ($keyword ?? '');
$rows = is_array($rows ?? null) ? $rows : [];
$notice = (string) ($notice ?? '');
$noticeText = match ($notice) {
    'updated' => '�� c?p nh?t tr?ng th�i b�o c�o.',
    'update_failed' => 'Kh�ng th? c?p nh?t tr?ng th�i b�o c�o.',
    default => '',
};

$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$isDeleted = static fn($value): bool => trim((string) $value) !== '';
$isContentDeleted = static function (array $row) use ($isDeleted): bool {
    $status = (string) ($row['content_status'] ?? '');
    if ($status === 'deleted') {
        return true;
    }

    if ($isDeleted($row['content_deleted_at'] ?? null) || $isDeleted($row['deleted_at'] ?? null)) {
        return true;
    }

    // Backward compatibility: current payload may not expose deleted_at yet.
    $hasDeletedMeta = array_key_exists('content_deleted_at', $row) || array_key_exists('deleted_at', $row);
    return !$hasDeletedMeta && $status === 'rejected';
};

$badgeByKind = [
    'recipe' => ['B�i dang', 'bg-indigo-100 text-indigo-700'],
    'comment' => ['B�nh lu?n', 'bg-amber-100 text-amber-700'],
    'tip' => ['M?o v?t', 'bg-cyan-100 text-cyan-700'],
    'ingredient' => ['Nguy�n li?u', 'bg-teal-100 text-teal-700'],
    'account' => ['T�i kho?n', 'bg-rose-100 text-rose-700'],
];

$commonHidden = static function (array $row) use ($status, $type, $keyword): array {
    return [
        'report_id' => (int) ($row['id'] ?? 0),
        'kind' => (string) ($row['kind'] ?? ''),
        'content_type' => (string) ($row['content_type'] ?? 'recipe'),
        'target_id' => (int) ($row['target_id'] ?? 0),
        'target_comment_id' => (int) ($row['target_comment_id'] ?? 0),
        'target_user_id' => (int) ($row['target_user_id'] ?? 0),
        'return_status' => $status,
        'return_type' => $type,
        'return_q' => $keyword,
    ];
};

$renderHiddenInputs = static function (array $fields) use ($e): void {
    foreach ($fields as $name => $value) {
        echo '<input type="hidden" name="' . $e($name) . '" value="' . $e($value) . '">';
    }
};

$renderActionForm = static function (
    array $fields,
    string $action,
    string $label,
    string $confirm,
    string $btnClass
) use ($renderHiddenInputs, $e): void {
    echo '<form method="post" action="' . URLROOT . '/admin/reports/action" onsubmit="return confirm(' . json_encode($confirm, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) . ');">';
    echo csrf_field();
    $fields['action'] = $action;
    $renderHiddenInputs($fields);
    echo '<button type="submit" class="rounded border px-2 py-1 text-xs font-semibold ' . $btnClass . '">' . $e($label) . '</button>';
    echo '</form>';
};

$renderActionDropdown = static function (
    array $fields,
    string $summaryLabel,
    string $summaryClass,
    string $action,
    string $confirm,
    string $durationField,
    string $durationLabel,
    string $reasonDefault
) use ($renderHiddenInputs, $e): void {
    echo '<details class="relative">';
    echo '<summary class="list-none cursor-pointer rounded border px-2 py-1 text-xs font-semibold ' . $summaryClass . '">' . $e($summaryLabel) . '</summary>';
    echo '<div class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">';
    echo '<form method="post" action="' . URLROOT . '/admin/reports/action" class="space-y-2" onsubmit="return confirm(' . json_encode($confirm, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) . ');">';
    echo csrf_field();
    $fields['action'] = $action;
    $renderHiddenInputs($fields);
    echo '<label class="block text-xs font-semibold text-slate-600">' . $e($durationLabel) . '</label>';
    echo '<select name="' . $e($durationField) . '" class="w-full rounded border border-slate-300 px-2 py-1 text-xs">';
    echo '<option value="3">3 ng�y</option>';
    echo '<option value="7" selected>7 ng�y</option>';
    echo '<option value="30">30 ng�y</option>';
    echo '<option value="0">Vinh vi?n</option>';
    echo '</select>';
    echo '<label class="block text-xs font-semibold text-slate-600">L� do</label>';
    echo '<textarea name="action_reason" rows="2" class="w-full rounded border border-slate-300 px-2 py-1 text-xs" placeholder="Nh?p l� do...">' . $e($reasonDefault) . '</textarea>';
    echo '<button type="submit" class="rounded border px-2 py-1 text-xs font-semibold ' . $summaryClass . '">�p d?ng</button>';
    echo '</form></div></details>';
};

$COMMON_ACTIONS = [
    'toggle_account_ban' => [
        'group' => 'user',
        'state' => static fn(array $r): bool => !empty($r['has_account_ban']),
        'actions' => [
            true => [
                'action' => 'user_unban_account',
                'label' => 'M? kh�a t�i kho?n',
                'confirm' => 'M? kh�a t�i kho?n n�y?',
                'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
            ],
            false => [
                'action' => 'user_ban_account',
                'label' => 'Kh�a t�i kho?n',
                'confirm' => 'X�c nh?n kh�a t�i kho?n n�y?',
                'with_duration' => 'ban_days',
                'duration_label' => 'Th?i gian kh�a t�i kho?n',
                'reason_default' => static fn(array $r): string => (($r['kind'] ?? '') === 'comment')
                    ? 'Vi ph?m b�nh lu?n b? b�o c�o'
                    : ((($r['kind'] ?? '') === 'account') ? 'B�o c�o t�i kho?n vi ph?m' : 'Vi ph?m n?i dung b? b�o c�o'),
                'class' => 'border-rose-300 text-rose-700 hover:bg-rose-50',
            ],
        ],
    ],
];

$ACTION_MAP = [
    'recipe' => array_merge([
        'toggle_hide' => [
            'group' => 'content',
            'visible' => static fn(array $r): bool => !$isDeleted($r['recipe_deleted_at'] ?? null),
            'state' => static fn(array $r): bool => (string) ($r['recipe_status'] ?? '') === 'rejected',
            'actions' => [
                true => [
                    'action' => 'recipe_unhide',
                    'label' => 'G? ?n b�i',
                    'confirm' => 'G? ?n c�ng th?c n�y?',
                    'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
                ],
                false => [
                    'action' => 'recipe_hide',
                    'label' => '?n b�i',
                    'confirm' => '?n c�ng th?c n�y?',
                    'class' => 'border-amber-300 text-amber-700 hover:bg-amber-50',
                ],
            ],
        ],
        'toggle_delete' => [
            'group' => 'content',
            'state' => static fn(array $r): bool => $isDeleted($r['recipe_deleted_at'] ?? null),
            'actions' => [
                true => [
                    'action' => 'recipe_restore',
                    'label' => 'Kh�i ph?c b�i',
                    'confirm' => 'Kh�i ph?c c�ng th?c n�y?',
                    'class' => 'border-sky-300 text-sky-700 hover:bg-sky-50',
                ],
                false => [
                    'action' => 'recipe_delete',
                    'label' => 'X�a b�i',
                    'confirm' => 'X�a c�ng th?c n�y?',
                    'class' => 'border-rose-300 text-rose-700 hover:bg-rose-50',
                ],
            ],
        ],
        'toggle_post_lock' => [
            'group' => 'user',
            'state' => static fn(array $r): bool => !empty($r['has_recipe_lock']),
            'actions' => [
                true => [
                    'action' => 'user_recipe_unlock',
                    'label' => 'G? kh�a dang',
                    'confirm' => 'G? kh�a dang cho t�i kho?n n�y?',
                    'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
                ],
                false => [
                    'action' => 'user_recipe_lock',
                    'label' => 'Kh�a dang',
                    'confirm' => 'X�c nh?n kh�a quy?n dang b�i?',
                    'with_duration' => 'lock_days',
                    'duration_label' => 'Th?i gian kh�a dang',
                    'reason_default' => 'Vi ph?m c�ng th?c b? b�o c�o',
                    'class' => 'border-indigo-300 text-indigo-700 hover:bg-indigo-50',
                ],
            ],
        ],
    ], $COMMON_ACTIONS),

    'comment' => array_merge([
        'toggle_hide' => [
            'group' => 'content',
            'visible' => static fn(array $r): bool => (string) ($r['comment_status'] ?? 'active') !== 'deleted',
            'state' => static fn(array $r): bool => (string) ($r['comment_status'] ?? 'active') === 'hidden',
            'actions' => [
                true => [
                    'action' => 'comment_unhide',
                    'label' => 'G? ?n cmt',
                    'confirm' => 'G? ?n b�nh lu?n n�y?',
                    'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
                ],
                false => [
                    'action' => 'comment_hide',
                    'label' => '?n cmt',
                    'confirm' => '?n b�nh lu?n n�y?',
                    'class' => 'border-amber-300 text-amber-700 hover:bg-amber-50',
                ],
            ],
        ],
        'toggle_delete' => [
            'group' => 'content',
            'state' => static fn(array $r): bool => (string) ($r['comment_status'] ?? 'active') === 'deleted',
            'actions' => [
                true => [
                    'action' => 'comment_restore',
                    'label' => 'Kh�i ph?c cmt',
                    'confirm' => 'Kh�i ph?c b�nh lu?n n�y?',
                    'class' => 'border-sky-300 text-sky-700 hover:bg-sky-50',
                ],
                false => [
                    'action' => 'comment_delete',
                    'label' => 'X�a b�nh lu?n',
                    'confirm' => 'X�a b�nh lu?n n�y?',
                    'class' => 'border-rose-300 text-rose-700 hover:bg-rose-50',
                ],
            ],
        ],
        'toggle_comment_lock' => [
            'group' => 'user',
            'state' => static fn(array $r): bool => !empty($r['has_comment_lock']),
            'actions' => [
                true => [
                    'action' => 'user_comment_unlock',
                    'label' => 'G? kh�a b�nh lu?n',
                    'confirm' => 'G? kh�a b�nh lu?n cho ngu?i d�ng n�y?',
                    'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
                ],
                false => [
                    'action' => 'user_comment_lock',
                    'label' => 'Kh�a b�nh lu?n',
                    'confirm' => 'X�c nh?n kh�a quy?n b�nh lu?n?',
                    'with_duration' => 'lock_days',
                    'duration_label' => 'Th?i gian kh�a b�nh lu?n',
                    'reason_default' => 'Vi ph?m b�nh lu?n b? b�o c�o',
                    'class' => 'border-indigo-300 text-indigo-700 hover:bg-indigo-50',
                ],
            ],
        ],
    ], $COMMON_ACTIONS),

    'tip' => array_merge([
        'toggle_hide' => [
            'group' => 'content',
            'state' => static fn(array $r): bool => (string) ($r['content_status'] ?? '') === 'rejected',
            'actions' => [
                true => [
                    'action' => 'content_unhide',
                    'label' => 'G? ?n',
                    'confirm' => 'G? ?n n?i dung n�y?',
                    'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
                ],
                false => [
                    'action' => 'content_hide',
                    'label' => '?n',
                    'confirm' => '?n n?i dung n�y?',
                    'class' => 'border-amber-300 text-amber-700 hover:bg-amber-50',
                ],
            ],
        ],
        'toggle_delete' => [
            'group' => 'content',
            'state' => static fn(array $r): bool => $isContentDeleted($r),
            'actions' => [
                true => [
                    'action' => 'content_restore',
                    'label' => 'Kh�i ph?c',
                    'confirm' => 'Kh�i ph?c n?i dung n�y?',
                    'class' => 'border-sky-300 text-sky-700 hover:bg-sky-50',
                ],
                false => [
                    'action' => 'content_delete',
                    'label' => 'X�a',
                    'confirm' => 'X�a n?i dung n�y?',
                    'class' => 'border-rose-300 text-rose-700 hover:bg-rose-50',
                ],
            ],
        ],
        'toggle_post_lock' => [
            'group' => 'user',
            'state' => static fn(array $r): bool => !empty($r['has_tip_lock']),
            'actions' => [
                true => [
                    'action' => 'user_tip_unlock',
                    'label' => 'G? kh�a dang m?o',
                    'confirm' => 'G? kh�a dang m?o cho t�i kho?n n�y?',
                    'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
                ],
                false => [
                    'action' => 'user_tip_lock',
                    'label' => 'Kh�a dang m?o',
                    'confirm' => 'X�c nh?n kh�a dang m?o?',
                    'with_duration' => 'lock_days',
                    'duration_label' => 'Th?i gian kh�a dang',
                    'reason_default' => 'Vi ph?m m?o v?t b? b�o c�o',
                    'class' => 'border-indigo-300 text-indigo-700 hover:bg-indigo-50',
                ],
            ],
        ],
    ], $COMMON_ACTIONS),

    'ingredient' => array_merge([
        'toggle_hide' => [
            'group' => 'content',
            'state' => static fn(array $r): bool => (string) ($r['content_status'] ?? '') === 'rejected',
            'actions' => [
                true => [
                    'action' => 'content_unhide',
                    'label' => 'G? ?n',
                    'confirm' => 'G? ?n nguy�n li?u n�y?',
                    'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
                ],
                false => [
                    'action' => 'content_hide',
                    'label' => '?n',
                    'confirm' => '?n nguy�n li?u n�y?',
                    'class' => 'border-amber-300 text-amber-700 hover:bg-amber-50',
                ],
            ],
        ],
        'toggle_delete' => [
            'group' => 'content',
            'state' => static fn(array $r): bool => $isContentDeleted($r),
            'actions' => [
                true => [
                    'action' => 'content_restore',
                    'label' => 'Kh�i ph?c',
                    'confirm' => 'Kh�i ph?c nguy�n li?u n�y?',
                    'class' => 'border-sky-300 text-sky-700 hover:bg-sky-50',
                ],
                false => [
                    'action' => 'content_delete',
                    'label' => 'X�a',
                    'confirm' => 'X�a nguy�n li?u n�y?',
                    'class' => 'border-rose-300 text-rose-700 hover:bg-rose-50',
                ],
            ],
        ],
        'toggle_post_lock' => [
            'group' => 'user',
            'state' => static fn(array $r): bool => !empty($r['has_ingredient_lock']),
            'actions' => [
                true => [
                    'action' => 'user_ingredient_unlock',
                    'label' => 'G? kh�a dang nguy�n li?u',
                    'confirm' => 'G? kh�a dang nguy�n li?u cho t�i kho?n n�y?',
                    'class' => 'border-emerald-300 text-emerald-700 hover:bg-emerald-50',
                ],
                false => [
                    'action' => 'user_ingredient_lock',
                    'label' => 'Kh�a dang nguy�n li?u',
                    'confirm' => 'X�c nh?n kh�a dang nguy�n li?u?',
                    'with_duration' => 'lock_days',
                    'duration_label' => 'Th?i gian kh�a dang',
                    'reason_default' => 'Vi ph?m nguy�n li?u b? b�o c�o',
                    'class' => 'border-indigo-300 text-indigo-700 hover:bg-indigo-50',
                ],
            ],
        ],
    ], $COMMON_ACTIONS),

    'account' => array_merge([
        'warn_account' => [
            'group' => 'user',
            'actions' => [
                false => [
                    'action' => 'user_warn',
                    'label' => 'C?nh c�o',
                    'confirm' => 'G?i c?nh c�o t?i t�i kho?n n�y?',
                    'class' => 'border-yellow-300 text-yellow-700 hover:bg-yellow-50',
                ],
            ],
        ],
    ], $COMMON_ACTIONS),
];

$renderActions = static function (
    array $row,
    array $actionMap,
    callable $renderForm,
    callable $renderDropdown,
    callable $buildHidden
): void {
    $kind = (string) ($row['kind'] ?? 'recipe');
    if (!isset($actionMap[$kind]) || !is_array($actionMap[$kind])) {
        return;
    }

    $hidden = $buildHidden($row);
    $contentActions = [];
    $userActions = [];

    foreach ($actionMap[$kind] as $key => $config) {
        if (!is_array($config)) {
            continue;
        }

        if (isset($config['visible']) && is_callable($config['visible']) && !$config['visible']($row)) {
            continue;
        }

        $state = false;
        if (isset($config['state']) && is_callable($config['state'])) {
            $state = (bool) $config['state']($row);
        }

        $actionData = $config['actions'][$state] ?? null;
        if (!is_array($actionData)) {
            continue;
        }

        $action = (string) ($actionData['action'] ?? '');
        $label = (string) ($actionData['label'] ?? '');
        $confirm = (string) ($actionData['confirm'] ?? 'X�c nh?n thao t�c n�y?');
        if ($action === '' || $label === '') {
            continue;
        }

        $defaultClass = match ($config['group'] ?? 'content') {
            'user' => 'border-indigo-300 text-indigo-700 hover:bg-indigo-50',
            default => 'border-slate-300 text-slate-700 hover:bg-slate-50',
        };
        $btnClass = (string) ($actionData['class'] ?? $defaultClass);
        $fields = $hidden;
        $fields['action_key'] = (string) $key;
        $priority = (int) ($actionData['priority'] ?? $config['priority'] ?? 0);

        ob_start();

        if (!empty($actionData['with_duration'])) {
            $durationField = (string) $actionData['with_duration'];
            $durationLabel = $actionData['duration_label'] ?? 'Th?i gian';
            if (is_callable($durationLabel)) {
                $durationLabel = (string) $durationLabel($row);
            }

            $reasonDefault = $actionData['reason_default'] ?? 'Vi ph?m n?i dung b? b�o c�o';
            if (is_callable($reasonDefault)) {
                $reasonDefault = (string) $reasonDefault($row);
            }

            $renderDropdown(
                $fields,
                $label,
                $btnClass,
                $action,
                $confirm,
                $durationField,
                (string) $durationLabel,
                (string) $reasonDefault
            );
        } else {
            $renderForm($fields, $action, $label, $confirm, $btnClass);
        }

        $item = [
            'priority' => $priority,
            'html' => (string) ob_get_clean(),
        ];

        if (($config['group'] ?? 'content') === 'user') {
            $userActions[] = $item;
        } else {
            $contentActions[] = $item;
        }
    }

    $sortByPriority = static function (array &$items): void {
        usort($items, static fn(array $a, array $b): int => ($a['priority'] ?? 0) <=> ($b['priority'] ?? 0));
    };

    $sortByPriority($contentActions);
    $sortByPriority($userActions);

    if (!empty($contentActions)) {
        echo '<div class="flex flex-wrap items-center gap-2">';
        foreach ($contentActions as $item) {
            echo $item['html'];
        }
        echo '</div>';
    }

    if (!empty($userActions)) {
        echo '<div class="flex flex-wrap items-center gap-2 border-l border-slate-200 pl-2 ml-1">';
        foreach ($userActions as $item) {
            echo $item['html'];
        }
        echo '</div>';
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">B�o c�o vi ph?m</h1>
        <p class="text-sm text-slate-500">Qu?n l� chung b�o c�o b�i dang v� b�nh lu?n trong m?t m�n h�nh.</p>
    </div>

    <?php if ($noticeText !== ''): ?>
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <?= $e($noticeText); ?>
        </div>
    <?php endif; ?>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/reports" class="flex flex-wrap items-center gap-3">
            <input
                type="text"
                name="q"
                value="<?= $e($keyword); ?>"
                placeholder="T�m theo ti�u d?, l� do, ngu?i b�o c�o"
                class="w-80 max-w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            >
            <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="" <?= $type === '' ? 'selected' : ''; ?>>T?t c? lo?i</option>
                <option value="recipe" <?= $type === 'recipe' ? 'selected' : ''; ?>>B�i dang</option>
                <option value="tip" <?= $type === 'tip' ? 'selected' : ''; ?>>M?o v?t</option>
                <option value="ingredient" <?= $type === 'ingredient' ? 'selected' : ''; ?>>Nguy�n li?u</option>
                <option value="comment" <?= $type === 'comment' ? 'selected' : ''; ?>>B�nh lu?n</option>
                <option value="account" <?= $type === 'account' ? 'selected' : ''; ?>>T�i kho?n</option>
            </select>
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="" <?= $status === '' ? 'selected' : ''; ?>>T?t c? tr?ng th�i</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : ''; ?>>Ch? x? l�</option>
                <option value="reviewed" <?= $status === 'reviewed' ? 'selected' : ''; ?>>�� xem</option>
                <option value="resolved" <?= $status === 'resolved' ? 'selected' : ''; ?>>�� x? l�</option>
            </select>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">L?c</button>
        </form>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="font-semibold text-slate-900">Danh s�ch b�o c�o (<?= count($rows); ?>)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">ID</th>
                        <th class="px-4 py-3 font-semibold">Lo?i</th>
                        <th class="px-4 py-3 font-semibold">N?i dung b? b�o c�o</th>
                        <th class="px-4 py-3 font-semibold">N?i dung b�nh lu?n</th>
                        <th class="px-4 py-3 font-semibold">L� do</th>
                        <th class="px-4 py-3 font-semibold">Ngu?i b�o c�o</th>
                        <th class="px-4 py-3 font-semibold">Tr?ng th�i</th>
                        <th class="px-4 py-3 font-semibold">Th?i gian</th>
                        <th class="px-4 py-3 font-semibold">X? l�</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-slate-500">Chua c� b�o c�o ph� h?p b? l?c.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $kind = (string) ($row['kind'] ?? 'recipe');
                        $badge = $badgeByKind[$kind] ?? $badgeByKind['recipe'];
                        if ($kind === 'comment' && trim((string) ($row['meta'] ?? '')) !== '') {
                            $badge[0] = (string) $row['meta'];
                        }
                        ?>
                        <tr>
                            <td class="px-4 py-3">#<?= (int) ($row['id'] ?? 0); ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $e($badge[1]); ?>"><?= $e($badge[0]); ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="<?= $e($row['target_link'] ?? '#'); ?>" class="hover:text-primary hover:underline">
                                    <?= $e($row['target_title'] ?? 'N?i dung d� x�a'); ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 max-w-[360px]">
                                <?php if ($kind === 'comment' || $kind === 'account'): ?>
                                    <div class="line-clamp-2 text-slate-700"><?= $e($row['comment_content'] ?? ''); ?></div>
                                <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 max-w-[320px]">
                                <div class="line-clamp-2 text-slate-700"><?= $e($row['reason'] ?? ''); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?= $e($row['reporter_name'] ?? '?n danh'); ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"><?= $e($row['status'] ?? ''); ?></span>
                            </td>
                            <td class="px-4 py-3 text-slate-500"><?= $e($row['created_at'] ?? ''); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="<?= $e($row['target_link'] ?? '#'); ?>" class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Xem</a>

                                    <?php $renderActions($row, $ACTION_MAP, $renderActionForm, $renderActionDropdown, $commonHidden); ?>

                                    <form method="post" action="<?= URLROOT; ?>/admin/reports/status">
                                        <?= csrf_field(); ?>
                                        <?php $renderHiddenInputs([
                                            'report_id' => (int) ($row['id'] ?? 0),
                                            'kind' => (string) ($row['kind'] ?? ''),
                                            'content_type' => (string) ($row['content_type'] ?? 'recipe'),
                                            'status' => 'resolved',
                                            'return_status' => $status,
                                            'return_type' => $type,
                                            'return_q' => $keyword,
                                        ]); ?>
                                        <button type="submit" class="rounded border border-emerald-300 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">B? qua b�o c�o</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
