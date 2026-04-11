<?php
$pageTitle = $title ?? SITENAME;
$userNotifications = [];
$unreadNotificationCount = 0;
if (is_logged_in()) {
    require_once APPROOT . '/app/models/NotificationModel.php';
    $notificationModel = new NotificationModel();
    $uid = (int) (current_user_id() ?? 0);
    if ($uid > 0) {
        $userNotifications = $notificationModel->recentForUser($uid, 8);
        $unreadNotificationCount = $notificationModel->unreadCountForUser($uid);
    }
}

$notificationTypeLabel = static function (string $type): string {
    return match ($type) {
        'follow' => 'Theo dõi mới',
        'comment' => 'Bình luận mới',
        'comment_reply' => 'Trả lời bình luận',
        'moderation_relationship' => 'Điều phối mối quan hệ',
        'moderation_follow_lock' => 'Khóa theo dõi',
        'moderation_follow_unlock' => 'Mở khóa theo dõi',
        'system_announcement' => 'Thông báo hệ thống',
        'report_comment' => 'Báo cáo bình luận',
        'report_recipe' => 'Báo cáo công thức',
        'report_tip' => 'Báo cáo mẹo vặt',
        'report_ingredient' => 'Báo cáo nguyên liệu',
        default => ucwords(str_replace('_', ' ', $type)),
    };
};

$urlRootPath = parse_url(URLROOT, PHP_URL_PATH) ?? '';
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$routePath = $urlRootPath !== '' && str_starts_with($currentUri, $urlRootPath) 
    ? substr($currentUri, strlen($urlRootPath)) 
    : $currentUri;
$routePath = trim($routePath, '/');
$routeParts = explode('/', $routePath);
$routeBase = $routeParts[0] ?? '';

$isHome = $routeBase === '';
$isMyRecipes = $routeBase === 'recipes' && ($routeParts[1] ?? '') === 'my';
$isRecipes = $routeBase === 'recipes' && !$isMyRecipes;
$isIngredients = $routeBase === 'ingredients';
$isTips = $routeBase === 'tips';
$isPosts = $routeBase === 'posts';
$isFeaturesGroup = in_array($routeBase, ['ai', 'quiz', 'quizzes']);
$isPersonalGroup = in_array($routeBase, ['meal-plans']) || $isMyRecipes;
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> - <?= SITENAME; ?></title>
    <?php if (!empty($useRecipeHubLayout)): ?>
        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700&amp;display=swap">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap">
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: { primary: '#f59f0a', 'background-light': '#f8f7f5', 'background-dark': '#221c10' },
                        fontFamily: { display: ['Work Sans'] }
                    }
                }
            };
        </script>
        <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/details-summary.css">
        <style>
            body { font-family: 'Work Sans', sans-serif; }
            .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
            .nav-hover-underline { position: relative; }
            .nav-hover-underline::after { content: ''; position: absolute; left: 0; bottom: -4px; width: 100%; height: 2px; background-color: #f59f0a; transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease-out; }
            .nav-hover-underline:hover::after, .group:hover .nav-hover-underline::after, details[open] .nav-hover-underline::after, .nav-active::after { transform: scaleX(1); }
        </style>
    <?php else: ?>
        <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/style.css">
        <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/details-summary.css">
    <?php endif; ?>
</head>
<?php if (!empty($useRecipeHubLayout)): ?>
<body class="bg-background-light text-slate-900 font-display">
<div class="relative flex min-h-screen flex-col overflow-x-hidden">
<header class="fixed top-0 z-50 w-full border-b border-primary/10 bg-background-light/80 px-4 py-4 backdrop-blur-md md:px-10 lg:px-20">
    <div class="mx-auto flex max-w-[1440px] items-center justify-between gap-8">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white">
                    <span class="material-symbols-outlined">restaurant_menu</span>
                </div>
                <h2 class="text-xl font-bold tracking-tight">Công thức Ngon</h2>
            </div>
            <nav class="hidden items-center gap-8 lg:flex">
                <a class="nav-hover-underline <?= $isHome ? 'nav-active text-primary' : 'text-slate-900' ?> text-sm font-semibold hover:text-primary transition-colors duration-300" href="<?= URLROOT; ?>/">Trang chủ</a>
                <a class="nav-hover-underline <?= $isRecipes ? 'nav-active text-primary' : 'text-slate-900' ?> text-sm font-semibold hover:text-primary transition-colors duration-300" href="<?= URLROOT; ?>/recipes">Công thức</a>
                <a class="nav-hover-underline <?= $isIngredients ? 'nav-active text-primary' : 'text-slate-900' ?> text-sm font-semibold hover:text-primary transition-colors duration-300" href="<?= URLROOT; ?>/ingredients">Nguyên liệu</a>
                <a class="nav-hover-underline <?= $isTips ? 'nav-active text-primary' : 'text-slate-900' ?> text-sm font-semibold hover:text-primary transition-colors duration-300" href="<?= URLROOT; ?>/tips">Mẹo vặt</a>
                <a class="nav-hover-underline <?= $isPosts ? 'nav-active text-primary' : 'text-slate-900' ?> text-sm font-semibold hover:text-primary transition-colors duration-300" href="<?= URLROOT; ?>/posts">Cộng đồng</a>
                <?php if (is_logged_in()): ?>
                <details class="relative group">
                    <summary class="list-none cursor-pointer text-sm font-semibold hover:text-primary flex items-center gap-1 pb-4 -mb-4 transition-colors duration-300 group-hover:text-primary <?= $isFeaturesGroup ? 'text-primary' : 'text-slate-900' ?>">
                        <span class="nav-hover-underline <?= $isFeaturesGroup ? 'nav-active' : '' ?>">Tính năng</span> <span class="material-symbols-outlined text-[16px]">expand_more</span>
                    </summary>
                    <div class="absolute left-0 top-full w-56 hidden group-hover:block open:block z-50">
                        <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            <a class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors duration-200 hover:text-primary" href="<?= URLROOT; ?>/ai/ingredient-vision">AI</a>
                            <a class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors duration-200 hover:text-primary" href="<?= URLROOT; ?>/ai/ingredient-drag-search">Kéo thả nguyên liệu</a>
                            <a class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors duration-200 hover:text-primary" href="<?= URLROOT; ?>/quiz">Quiz</a>
                        </div>
                    </div>
                </details>
                <?php endif; ?>
                <?php if (is_logged_in()): ?>
                <details class="relative group pb-1">
                    <summary class="list-none cursor-pointer text-sm font-semibold hover:text-primary flex items-center gap-1 pb-4 -mb-4 transition-colors duration-300 group-hover:text-primary <?= $isPersonalGroup ? 'text-primary' : 'text-slate-900' ?>">
                        <span class="nav-hover-underline <?= $isPersonalGroup ? 'nav-active' : '' ?>">Cá nhân</span> <span class="material-symbols-outlined text-[16px]">expand_more</span>
                    </summary>
                    <div class="absolute left-0 top-full w-48 hidden group-hover:block open:block z-50">
                        <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            <a class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors duration-200 hover:text-primary" href="<?= URLROOT; ?>/recipes/my">Công thức của tôi</a>
                            <a class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors duration-200 hover:text-primary" href="<?= URLROOT; ?>/meal-plans">Lập kế hoạch</a>
                        </div>
                    </div>
                </details>
                <?php endif; ?>
            </nav>
            <details class="relative lg:hidden">
                <summary class="cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                    Menu
                </summary>
                <div class="absolute left-0 top-[calc(100%+8px)] z-50 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-lg max-h-[80vh] overflow-y-auto">
                    <nav class="flex flex-col gap-1">
                        <div class="px-3 py-2 text-xs font-bold uppercase tracking-wider text-slate-400">Khám phá</div>
                        <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/">Trang chủ</a>
                        <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/recipes">Công thức</a>
                        <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/ingredients">Nguyên liệu</a>

                        <div class="my-1 border-t border-slate-100"></div>
                        <div class="px-3 py-2 text-xs font-bold uppercase tracking-wider text-slate-400">Tính năng</div>
                        <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/tips">Mẹo vặt</a>
                        <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/ai/ingredient-vision">AI</a>
                        <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/ai/ingredient-drag-search">Kéo thả nguyên liệu</a>
                        <?php if (is_logged_in()): ?>
                            <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/meal-plans">Lập kế hoạch</a>
                        <?php endif; ?>

                        <?php if (is_logged_in()): ?>
                        <div class="my-1 border-t border-slate-100"></div>
                        <div class="px-3 py-2 text-xs font-bold uppercase tracking-wider text-slate-400">Cá nhân</div>
                        <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/recipes/my">Công thức của tôi</a>
                        <a class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" href="#" onclick="document.querySelector('#notification-summary')?.click(); return false;">Thông báo</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </details>
        </div>
        <div class="flex items-center gap-4">
            <?php if (is_logged_in()): ?>
                <details class="relative">
                    <summary id="notification-summary" class="list-none cursor-pointer rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                        <span class="material-symbols-outlined align-middle">notifications</span>
                        Thông báo<?= $unreadNotificationCount > 0 ? ' (' . $unreadNotificationCount . ')' : ''; ?>
                    </summary>
                    <div class="absolute right-0 z-50 mt-2 w-96 max-w-[90vw] rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                        <?php if ($userNotifications === []): ?>
                            <div class="px-3 py-4 text-sm text-slate-500">Chưa có thông báo.</div>
                        <?php else: ?>
                            <div class="max-h-96 overflow-y-auto">
                                <?php foreach ($userNotifications as $n): ?>
                                    <?php
                                    $isRead = (int) ($n['is_read'] ?? 0) === 1;
                                    $messageText = (string) ($n['message'] ?? '');
                                    $typeText = (string) ($n['type'] ?? 'notification');
                                    $typeLabel = $notificationTypeLabel($typeText);
                                    $timeText = (string) ($n['created_at'] ?? '');
                                    $copyText = '[' . $timeText . '] ' . $typeLabel . ': ' . $messageText;
                                    ?>
                                    <div class="block rounded-lg px-3 py-2 text-sm <?= $isRead ? 'bg-white' : 'bg-amber-50'; ?>">
                                        <div class="font-semibold text-slate-800"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-slate-600 whitespace-pre-wrap break-words"><?= htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="mt-1 flex items-center justify-between gap-2">
                                            <div class="text-xs text-slate-400"><?= htmlspecialchars($timeText, ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="rounded border border-slate-300 px-2 py-0.5 text-xs font-semibold text-slate-600 hover:bg-slate-50" onclick='navigator.clipboard.writeText(<?= json_encode($copyText, JSON_UNESCAPED_UNICODE); ?>)'>Copy</button>
                                                <a href="<?= URLROOT; ?>/notifications/<?= (int) ($n['id'] ?? 0); ?>/open" class="rounded border border-primary/30 px-2 py-0.5 text-xs font-semibold text-primary hover:bg-primary/10">Mở</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </details>
                <a class="text-sm font-semibold hover:text-primary" href="<?= URLROOT; ?>/profile">Hồ sơ</a>
                <form method="post" action="<?= URLROOT; ?>/logout">
                    <?= csrf_field(); ?>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white" type="submit">Đăng xuất</button>
                </form>
            <?php elseif (is_admin()): ?>
                <form method="post" action="<?= URLROOT; ?>/admin/logout">
                    <?= csrf_field(); ?>
                    <button class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white" type="submit">Đăng xuất admin</button>
                </form>
            <?php else: ?>
                <a class="text-sm font-semibold hover:text-primary" href="<?= URLROOT; ?>/login">Đăng nhập</a>
                <a class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white" href="<?= URLROOT; ?>/register">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-8 px-4 py-8 pt-24 md:flex-row md:px-10 lg:px-20">
<?php else: ?>
<body>
<header class="site-header site-header--fixed">
    <div class="container nav-wrap">
        <a class="brand" href="<?= URLROOT; ?>/">Website Nấu Ăn</a>
        <nav>
            <a href="<?= URLROOT; ?>/" class="<?= $isHome ? 'active' : '' ?>">Trang chủ</a>
            <a href="<?= URLROOT; ?>/recipes" class="<?= $isRecipes ? 'active' : '' ?>">Công thức</a>
            <a href="<?= URLROOT; ?>/ingredients" class="<?= $isIngredients ? 'active' : '' ?>">Nguyên liệu</a>
            <a href="<?= URLROOT; ?>/tips" class="<?= $isTips ? 'active' : '' ?>">Mẹo vặt</a>
            <a href="<?= URLROOT; ?>/posts" class="<?= $isPosts ? 'active' : '' ?>">Cộng đồng</a>
            
            <?php if (is_logged_in()): ?>
            <details style="position:relative;display:inline-block;">
                <summary style="cursor:pointer;list-style:none;" class="<?= $isFeaturesGroup ? 'active' : '' ?>">Tính năng ▼</summary>
                <div style="position:absolute;left:0;top:calc(100% + 6px);width:200px;background:#fff;border:1px solid #ddd;border-radius:8px;padding:8px;box-shadow:0 8px 24px rgba(0,0,0,.08);z-index:50;">
                    <a href="<?= URLROOT; ?>/ai/ingredient-vision" style="display:block;padding:8px;color:#333;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">AI</a>
                    <a href="<?= URLROOT; ?>/ai/ingredient-drag-search" style="display:block;padding:8px;color:#333;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">Kéo thả nguyên liệu</a>
                    <a href="<?= URLROOT; ?>/quizzes" style="display:block;padding:8px;color:#333;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">Quiz</a>
                </div>
            </details>

            <details style="position:relative;display:inline-block;">
                <summary style="cursor:pointer;list-style:none;" class="<?= $isPersonalGroup ? 'active' : '' ?>">Cá nhân ▼</summary>
                <div style="position:absolute;left:0;top:calc(100% + 6px);width:200px;background:#fff;border:1px solid #ddd;border-radius:8px;padding:8px;box-shadow:0 8px 24px rgba(0,0,0,.08);z-index:50;">
                    <a href="<?= URLROOT; ?>/recipes/my" style="display:block;padding:8px;color:#333;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">Công thức của tôi</a>
                    <a href="<?= URLROOT; ?>/meal-plans" style="display:block;padding:8px;color:#333;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">Lập kế hoạch</a>
                    <a href="<?= URLROOT; ?>/quizzes/my-certificates" style="display:block;padding:8px;color:#333;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">Chứng nhận</a>
                    <a href="#" onclick="document.querySelector('.notification-dropdown summary')?.click(); return false;" style="display:block;padding:8px;color:#333;text-decoration:none;border-radius:4px;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">Thông báo</a>
                </div>
            </details>
            
            <details class="notification-dropdown" style="position:relative;display:inline-block;">
                <summary style="cursor:pointer;display:none;">Thông báo<?= $unreadNotificationCount > 0 ? ' (' . $unreadNotificationCount . ')' : ''; ?></summary>
                <div style="position:absolute;right:0;top:calc(100% + 6px);width:360px;max-width:90vw;background:#fff;border:1px solid #ddd;border-radius:8px;padding:8px;box-shadow:0 8px 24px rgba(0,0,0,.08);z-index:50;">
                    <?php if ($userNotifications === []): ?>
                        <div style="padding:8px;color:#666;">Chưa có thông báo.</div>
                    <?php else: ?>
                        <div style="max-height:320px;overflow:auto;">
                            <?php foreach ($userNotifications as $n): ?>
                                <?php
                                $messageText = (string) ($n['message'] ?? '');
                                $typeText = (string) ($n['type'] ?? 'notification');
                                $typeLabel = $notificationTypeLabel($typeText);
                                $timeText = (string) ($n['created_at'] ?? '');
                                $copyText = '[' . $timeText . '] ' . $typeLabel . ': ' . $messageText;
                                ?>
                                <div style="display:block;padding:8px;border-radius:6px;margin-bottom:4px;background:<?= ((int) ($n['is_read'] ?? 0) === 1) ? '#fff' : '#fff8e1'; ?>;color:#222;">
                                    <div style="font-weight:600;"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div style="white-space:pre-wrap;word-break:break-word;"><?= htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:4px;">
                                        <div style="font-size:12px;color:#888;"><?= htmlspecialchars($timeText, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <button type="button" style="border:1px solid #d1d5db;border-radius:6px;padding:2px 8px;background:#fff;font-size:12px;cursor:pointer;" onclick='navigator.clipboard.writeText(<?= json_encode($copyText, JSON_UNESCAPED_UNICODE); ?>)'>Copy</button>
                                            <a href="<?= URLROOT; ?>/notifications/<?= (int) ($n['id'] ?? 0); ?>/open" style="border:1px solid #fdba74;border-radius:6px;padding:2px 8px;text-decoration:none;font-size:12px;color:#c2410c;background:#fff;">Mở</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </details>
            <a href="<?= URLROOT; ?>/profile">Hồ sơ</a>
                <form method="post" action="<?= URLROOT; ?>/logout" class="inline-form">
                    <?= csrf_field(); ?>
                    <button type="submit">Đăng xuất</button>
                </form>
            <?php elseif (is_admin()): ?>
                <form method="post" action="<?= URLROOT; ?>/admin/logout" class="inline-form">
                    <?= csrf_field(); ?>
                    <button type="submit">Đăng xuất admin</button>
                </form>
            <?php else: ?>
                <a href="<?= URLROOT; ?>/login">Đăng nhập</a>
                <a href="<?= URLROOT; ?>/register">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main--with-fixed-header">
<?php endif; ?>
<div id="chat-widget" class="fixed bottom-4 right-4 z-[9999]" style="position:fixed;right:16px;bottom:16px;z-index:9999;" data-chat-widget data-chat-endpoint="<?= URLROOT; ?>/chat" data-csrf-token="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <button type="button" data-chat-toggle class="rounded-full bg-primary px-4 py-3 text-sm font-bold text-white shadow-lg hover:opacity-95" style="padding:10px 14px;border:0;border-radius:999px;background:#f59f0a;color:#fff;font-weight:700;cursor:pointer;">
        Trợ lý Chat
    </button>
    <div data-chat-panel hidden class="mt-3 w-[340px] max-w-[90vw] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" style="margin-top:10px;width:340px;max-width:90vw;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 16px 40px rgba(15,23,42,.18);">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 class="text-sm font-bold text-slate-800">Chatbot hỗ trợ</h3>
            <button type="button" data-chat-close class="text-xs font-semibold text-slate-500">Đóng</button>
        </div>
        <div data-chat-messages class="max-h-80 space-y-2 overflow-y-auto bg-slate-50 p-3 text-sm"></div>
        <div class="space-y-2 border-t border-slate-100 p-3">
            <div class="flex flex-wrap gap-2" data-chat-suggestions>
                <button type="button" data-chat-quick="Tôi muốn đăng nhập vào tài khoản" class="rounded-full border border-slate-300 px-3 py-1 text-xs text-slate-700">Vào tài khoản</button>
                <button type="button" data-chat-quick="Có món ăn ít calo không?" class="rounded-full border border-slate-300 px-3 py-1 text-xs text-slate-700">Món ít calo</button>
                <button type="button" data-chat-quick="Xem kế hoạch bữa ăn của tôi ở đâu?" class="rounded-full border border-slate-300 px-3 py-1 text-xs text-slate-700">Kế hoạch bữa ăn</button>
            </div>
            <form data-chat-form class="flex gap-2">
                <input type="text" data-chat-input class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Nhập câu hỏi..." maxlength="300">
                <button type="button" data-chat-send class="rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-white">Gửi</button>
            </form>
        </div>
    </div>
</div>

