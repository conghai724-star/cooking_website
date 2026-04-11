<?php
$can = static fn(string $permission): bool => admin_has_permission($permission);
$canAny = static function (array $permissions) use ($can): bool {
    foreach ($permissions as $permission) {
        if ($can((string) $permission)) {
            return true;
        }
    }
    return false;
};

$overview = is_array($overview ?? null) ? $overview : [];
$latestUsers = is_array($latestUsers ?? null) ? $latestUsers : [];
$latestRecipes = is_array($latestRecipes ?? null) ? $latestRecipes : [];

$overviewCards = [
    [
        'permissions' => ['admin.users.view', 'admin.users.manage', 'admin.users.role.assign'],
        'title' => 'Tổng người dùng',
        'value' => number_format((int) ($overview['total_users'] ?? 0)),
        'note_class' => 'text-xs text-green-600 mt-2 flex items-center gap-1 font-medium',
        'note_icon' => 'trending_up',
        'note_text' => 'Người dùng mới ' . number_format((int) ($overview['new_users_last_30_days'] ?? 0)) . ' trong 30 ngày',
        'icon' => 'group',
        'link' => URLROOT . '/admin/users',
    ],
    [
        'permissions' => ['admin.recipes.manage', 'admin.recipes.review'],
        'title' => 'Tổng công thức',
        'value' => number_format((int) ($overview['total_recipes'] ?? 0)),
        'note_class' => 'text-xs text-green-600 mt-2 flex items-center gap-1 font-medium',
        'note_icon' => 'trending_up',
        'note_text' => 'Đang chờ duyệt: ' . number_format((int) ($overview['pending_recipes'] ?? 0)),
        'icon' => 'menu_book',
        'link' => URLROOT . '/admin/recipes',
    ],
    [
        'permissions' => ['admin.comments.moderate'],
        'title' => 'Tổng bình luận',
        'value' => number_format((int) ($overview['total_comments'] ?? 0)),
        'note_class' => 'text-xs text-slate-400 mt-2 font-medium',
        'note_icon' => '',
        'note_text' => 'Tất cả bình luận',
        'icon' => 'chat_bubble',
        'link' => URLROOT . '/admin/comments',
    ],
    [
        'permissions' => ['admin.reports.view', 'admin.reports.resolve'],
        'title' => 'Tổng báo cáo',
        'value' => number_format((int) ($overview['total_reports'] ?? 0)),
        'note_class' => 'text-xs text-red-600 mt-2 flex items-center gap-1 font-medium',
        'note_icon' => 'warning',
        'note_text' => 'Mới: ' . number_format((int) ($overview['pending_reports'] ?? 0)),
        'icon' => 'flag',
        'link' => URLROOT . '/admin/reports',
    ],
];

$quickActions = [
    [
        'permissions' => ['admin.recipes.manage'],
        'icon' => 'add_circle',
        'title' => 'Thêm công thức mới',
        'desc' => 'Tạo công thức trong admin',
        'href' => URLROOT . '/admin/recipes',
    ],
    [
        'permissions' => ['admin.recipes.review', 'admin.tips.review', 'admin.ingredients.review'],
        'icon' => 'verified_user',
        'title' => 'Duyệt nội dung',
        'desc' => 'Xem danh sách đang chờ duyệt',
        'href' => URLROOT . '/admin/reports',
    ],
    [
        'permissions' => ['admin.notifications.manage'],
        'icon' => 'mail',
        'title' => 'Gửi thông báo',
        'desc' => 'Gửi thông báo hệ thống',
        'href' => URLROOT . '/admin/notifications',
    ],
];

$showAnyOverview = false;
foreach ($overviewCards as $card) {
    if ($canAny($card['permissions'])) {
        $showAnyOverview = true;
        break;
    }
}
?>

<?php if ($showAnyOverview): ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php foreach ($overviewCards as $card): ?>
    <?php if (!$canAny($card['permissions'])) { continue; } ?>
    <a href="<?= htmlspecialchars((string) ($card['link'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="group bg-white p-6 rounded shadow-sm border border-slate-100 flex items-center justify-between transition hover:border-primary/50 hover:bg-slate-50">
        <div>
            <p class="text-slate-500 text-sm font-medium"><?= htmlspecialchars((string) $card['title'], ENT_QUOTES, 'UTF-8'); ?></p>
            <h3 class="text-2xl font-bold mt-1"><?= htmlspecialchars((string) $card['value'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="<?= htmlspecialchars((string) $card['note_class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ((string) ($card['note_icon'] ?? '') !== ''): ?>
                    <span class="material-symbols-outlined text-sm"><?= htmlspecialchars((string) $card['note_icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
                <?= htmlspecialchars((string) $card['note_text'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
        <div class="bg-primary/10 p-3 rounded-lg text-primary group-hover:bg-primary/20">
            <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars((string) $card['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?php if ($can('admin.stats.view') || $can('admin.dashboard.view')): ?>
    <div class="lg:col-span-2 bg-white p-6 rounded shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-6">
            <h4 class="font-bold text-slate-800">Tăng trưởng người dùng</h4>
            <select class="text-sm border-slate-200 rounded p-1 focus:ring-primary">
                <option>7 ngày qua</option>
                <option>30 ngày qua</option>
            </select>
        </div>
        <div class="h-64 flex items-end gap-2 pb-6 px-4">
            <div class="flex-1 bg-primary/20 rounded-t h-[40%]"></div>
            <div class="flex-1 bg-primary/20 rounded-t h-[60%]"></div>
            <div class="flex-1 bg-primary/20 rounded-t h-[55%]"></div>
            <div class="flex-1 bg-primary/40 rounded-t h-[75%]"></div>
            <div class="flex-1 bg-primary/20 rounded-t h-[65%]"></div>
            <div class="flex-1 bg-primary/60 rounded-t h-[90%]"></div>
            <div class="flex-1 bg-primary rounded-t h-[85%]"></div>
        </div>
        <div class="flex justify-between text-xs text-slate-400 font-medium px-4">
            <span>Th2</span><span>Th3</span><span>Th4</span><span>Th5</span><span>Th6</span><span>Th7</span><span>CN</span>
        </div>
    </div>
    <?php endif; ?>
    <div class="bg-white p-6 rounded shadow-sm border border-slate-100">
        <h4 class="font-bold text-slate-800 mb-6">Thao tác nhanh</h4>
        <div class="space-y-3">
            <?php foreach ($quickActions as $action): ?>
            <?php if (!$canAny($action['permissions'])) { continue; } ?>
            <a href="<?= htmlspecialchars((string) ($action['href'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" class="w-full block rounded bg-background-light hover:bg-slate-100 transition-colors text-left">
                <div class="flex items-center gap-3 p-4">
                    <span class="material-symbols-outlined text-primary"><?= htmlspecialchars((string) $action['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <div>
                        <p class="text-sm font-semibold"><?= htmlspecialchars((string) $action['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars((string) $action['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($can('admin.recipes.manage') || $can('admin.recipes.review')): ?>
<div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h4 class="font-bold text-slate-800">Công thức mới nhất</h4>
        <a href="<?= URLROOT; ?>/admin/recipes" class="text-primary text-sm font-semibold hover:underline">Xem tất cả</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-background-light text-slate-500">
            <tr>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Tiêu đề</th>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Tác giả</th>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Ngày đăng</th>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Trạng thái</th>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Hành động</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (empty($latestRecipes)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-6 text-center text-slate-500">Không có công thức mới.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($latestRecipes as $recipe): ?>
                    <?php
                    $recipeId = (int) ($recipe['id'] ?? 0);
                    $authorName = (string) ($recipe['author_name'] ?? 'Không rõ');
                    $title = (string) ($recipe['title'] ?? 'Không tiêu đề');
                    $status = (string) ($recipe['status'] ?? 'unknown');
                    $createdAt = (string) ($recipe['created_at'] ?? '');
                    $statusClasses = 'bg-slate-100 text-slate-700';
                    if ($status === 'approved') {
                        $statusClasses = 'bg-green-100 text-green-700';
                    } elseif ($status === 'pending') {
                        $statusClasses = 'bg-yellow-100 text-yellow-700';
                    } elseif ($status === 'rejected') {
                        $statusClasses = 'bg-rose-100 text-rose-700';
                    }
                    ?>
                    <tr>
                        <td class="px-6 py-4 font-medium text-slate-900">
                            <a class="hover:text-primary" href="<?= URLROOT; ?>/admin/recipes/<?= $recipeId; ?>"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a>
                        </td>
                        <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-6 py-4 text-slate-600"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 <?= $statusClasses; ?> text-xs font-bold rounded-full"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="<?= URLROOT; ?>/admin/recipes/<?= $recipeId; ?>" class="text-slate-400 hover:text-primary"><span class="material-symbols-outlined">visibility</span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($can('admin.users.view') || $can('admin.users.manage') || $can('admin.users.role.assign')): ?>
<div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h4 class="font-bold text-slate-800">Người dùng mới đăng ký</h4>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-100">
        <?php if (empty($latestUsers)): ?>
            <div class="p-6 text-slate-500">Không có người dùng mới.</div>
        <?php else: ?>
            <?php foreach ($latestUsers as $user): ?>
                <?php
                $userId = (int) ($user['id'] ?? 0);
                $userName = trim((string) ($user['name'] ?? $user['email'] ?? 'Người dùng'));
                $createdAt = (string) ($user['created_at'] ?? '');
                ?>
                <div class="p-6 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 overflow-hidden"></div>
                    <div>
                        <p class="font-bold text-sm"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

