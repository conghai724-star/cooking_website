<?php
$admin = current_admin() ?? [];
$adminName = $admin['name'] ?? 'Admin';
$adminRoleKey = (string) ($admin['role'] ?? 'admin');
$adminRoleMap = [
    'super_admin' => 'Super Admin',
    'mod' => 'Moderator',
    'support' => 'Support',
];
$adminRole = $adminRoleMap[$adminRoleKey] ?? ucfirst(str_replace('_', ' ', $adminRoleKey));
$can = static fn(string $permission): bool => admin_has_permission($permission);
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$basePath = (string) parse_url((string) URLROOT, PHP_URL_PATH);
$normalizePath = static function (string $path): string {
    $trimmed = rtrim($path, '/');
    return $trimmed === '' ? '/' : $trimmed;
};
$requestPath = $normalizePath($requestPath);
$basePath = $normalizePath($basePath);
$withoutBase = $requestPath;
if ($basePath !== '/' && str_starts_with($requestPath, $basePath)) {
    $withoutBase = substr($requestPath, strlen($basePath));
    $withoutBase = $withoutBase === '' ? '/' : $withoutBase;
}
$isActive = static function (string $adminPathPrefix, bool $exact = false) use ($withoutBase): bool {
    $normalized = '/' . ltrim($adminPathPrefix, '/');
    if ($withoutBase === $normalized || ($exact && $withoutBase === rtrim($normalized, '/'))) {
        return true;
    }
    if ($exact) {
        return false;
    }
    return str_starts_with($withoutBase, $normalized . '/');
};
$navClass = static function (bool $active): string {
    return $active
        ? 'flex items-center gap-3 px-4 py-3 rounded bg-primary/10 text-primary font-semibold'
        : 'flex items-center gap-3 px-4 py-3 rounded text-slate-600 hover:bg-slate-50 transition-colors';
};
$canAny = static function (array $permissions) use ($can): bool {
    foreach ($permissions as $permission) {
        if ($can((string) $permission)) {
            return true;
        }
    }
    return false;
};
$adminNavItems = [
    ['path' => '/admin', 'icon' => 'dashboard', 'label' => 'Bảng điều khiển', 'permissions' => ['admin.dashboard.view'], 'exact' => true],
    ['path' => '/admin/users', 'icon' => 'group', 'label' => 'Quản lý người dùng', 'permissions' => ['admin.users.view', 'admin.users.manage', 'admin.users.role.assign']],
    ['path' => '/admin/recipes', 'icon' => 'restaurant_menu', 'label' => 'Quản lý công thức', 'permissions' => ['admin.recipes.manage', 'admin.recipes.review']],
    ['path' => '/admin/quizzes', 'icon' => 'quiz', 'label' => 'Bộ câu hỏi', 'permissions' => ['admin.recipes.review']],
    ['path' => '/admin/tips', 'icon' => 'lightbulb', 'label' => 'Quản lý mẹo vặt', 'permissions' => ['admin.tips.manage', 'admin.tips.review']],
    ['path' => '/admin/ingredients', 'icon' => 'grocery', 'label' => 'Quản lý nguyên liệu', 'permissions' => ['admin.ingredients.manage', 'admin.ingredients.review']],
    ['path' => '/admin/categories', 'icon' => 'category', 'label' => 'Danh mục', 'permissions' => ['admin.categories.manage']],
    ['path' => '/admin/reports', 'icon' => 'report', 'label' => 'Báo cáo vi phạm', 'permissions' => ['admin.reports.view', 'admin.reports.resolve']],
    ['path' => '/admin/bans', 'icon' => 'gpp_bad', 'label' => 'Danh sách ban', 'permissions' => ['admin.users.ban']],
    ['path' => '/admin/ban-appeals', 'icon' => 'rule', 'label' => 'Khiếu nại ban', 'permissions' => ['admin.users.ban']],
    ['path' => '/admin/relationships', 'icon' => 'hub', 'label' => 'Mối quan hệ', 'permissions' => ['admin.relationships.view', 'admin.relationships.moderate']],
    ['path' => '/admin/banners', 'icon' => 'flag', 'label' => 'Banner', 'permissions' => ['admin.banners.manage']],
    ['path' => '/admin/notifications', 'icon' => 'notifications', 'label' => 'Thông báo', 'permissions' => ['admin.notifications.manage']],
    ['path' => '/admin/stats', 'icon' => 'monitoring', 'label' => 'Thống kê', 'permissions' => ['admin.stats.view']],
    ['path' => '/admin/mealplans', 'icon' => 'calendar_month', 'label' => 'Kế hoạch bữa ăn', 'permissions' => ['admin.mealplans.view', 'admin.mealplans.moderate']],
    ['path' => '/admin/logs', 'icon' => 'history', 'label' => 'Nhật ký hệ thống', 'permissions' => ['admin.logs.view']],
    ['path' => '/admin/chat-histories', 'icon' => 'chat', 'label' => 'Lịch sử chat', 'permissions' => ['admin.logs.view']],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quáº£n trá»‹ - <?= SITENAME; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#f59f0a',
                        'background-light': '#f8f7f5',
                        'background-dark': '#221c10'
                    },
                    fontFamily: {
                        display: ['Work Sans']
                    },
                    borderRadius: {
                        DEFAULT: '0.75rem',
                        lg: '1rem',
                        xl: '1.5rem',
                        full: '9999px'
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/details-summary.css">
    <style>
        body { font-family: 'Work Sans', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="bg-background-light text-slate-900 font-display">
<div class="flex min-h-screen">
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col h-screen sticky top-0">
        <div class="p-6 flex items-center gap-3">
            <div class="bg-primary rounded-lg p-2 text-white"><span class="material-symbols-outlined">skillet</span></div>
            <div>
                <h1 class="font-bold text-xl tracking-tight">RecipeAdmin</h1>
                <p class="text-xs text-slate-500">Cooking Portal</p>
            </div>
        </div>
        <nav class="flex-1 px-4 py-4 space-y-1">
            <?php foreach ($adminNavItems as $item): ?>
                <?php if (!$canAny($item['permissions'])) { continue; } ?>
                <a class="<?= $navClass($isActive((string) $item['path'], (bool) ($item['exact'] ?? false))); ?>" href="<?= URLROOT . $item['path']; ?>">
                    <span class="material-symbols-outlined"><?= htmlspecialchars((string) $item['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="p-4 border-t border-slate-100">
            <form method="post" action="<?= URLROOT; ?>/admin/logout">
                <?= csrf_field(); ?>
                <button class="w-full flex items-center gap-3 px-4 py-3 rounded text-red-500 hover:bg-red-50 transition-colors" type="submit">
                    <span class="material-symbols-outlined">logout</span>
                    <span>Đăng xuất</span>
                </button>
            </form>
        </div>
    </aside>
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <header class="h-16 bg-white border-b border-slate-200 px-8 flex items-center justify-between sticky top-0 z-10">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                    <input class="w-full pl-10 pr-4 py-2 bg-slate-100 border-none rounded-lg focus:ring-2 focus:ring-primary text-sm" placeholder="TĂ¬m cĂ´ng thA�»©c, ngA�°A�»i dĂ¹ng..." type="text">
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button class="p-2 text-slate-500 hover:bg-slate-100 rounded-full relative" type="button">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
                <div class="h-8 w-[1px] bg-slate-200 mx-2"></div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-sm font-semibold"><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="text-xs text-slate-500"><?= htmlspecialchars($adminRole, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-200 bg-cover bg-center border border-slate-300" style="background-image: url('https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=200&q=80');"></div>
                    <form method="post" action="<?= URLROOT; ?>/admin/logout">
                        <?= csrf_field(); ?>
                        <button class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50" type="submit">Đăng xuất</button>
                    </form>
                </div>
            </div>
        </header>
        <div class="p-8 space-y-8">

