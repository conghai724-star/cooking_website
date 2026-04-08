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

$overviewCards = [
    [
        'permissions' => ['admin.users.view', 'admin.users.manage', 'admin.users.role.assign'],
        'title' => 'Total Users',
        'value' => '12,450',
        'note_class' => 'text-xs text-green-600 mt-2 flex items-center gap-1 font-medium',
        'note_icon' => 'trending_up',
        'note_text' => '+12% this month',
        'icon' => 'group',
    ],
    [
        'permissions' => ['admin.recipes.manage', 'admin.recipes.review'],
        'title' => 'Total Recipes',
        'value' => '8,320',
        'note_class' => 'text-xs text-green-600 mt-2 flex items-center gap-1 font-medium',
        'note_icon' => 'trending_up',
        'note_text' => '+5% this month',
        'icon' => 'menu_book',
    ],
    [
        'permissions' => ['admin.comments.moderate'],
        'title' => 'Total Comments',
        'value' => '45,100',
        'note_class' => 'text-xs text-slate-400 mt-2 font-medium',
        'note_icon' => '',
        'note_text' => 'Across all platforms',
        'icon' => 'chat_bubble',
    ],
    [
        'permissions' => ['admin.reports.view', 'admin.reports.resolve'],
        'title' => 'Total Reports',
        'value' => '124',
        'note_class' => 'text-xs text-red-600 mt-2 flex items-center gap-1 font-medium',
        'note_icon' => 'warning',
        'note_text' => '12 urgent cases',
        'icon' => 'flag',
    ],
];

$quickActions = [
    [
        'permissions' => ['admin.recipes.manage'],
        'icon' => 'add_circle',
        'title' => 'New Recipe',
        'desc' => 'Add a curated featured recipe',
    ],
    [
        'permissions' => ['admin.recipes.review', 'admin.tips.review', 'admin.ingredients.review'],
        'icon' => 'verified_user',
        'title' => 'Approve Pending',
        'desc' => '24 recipes waiting review',
    ],
    [
        'permissions' => ['admin.notifications.manage'],
        'icon' => 'mail',
        'title' => 'Email Blast',
        'desc' => 'Send newsletter to all users',
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
    <div class="bg-white p-6 rounded shadow-sm border border-slate-100 flex items-center justify-between">
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
        <div class="bg-primary/10 p-3 rounded-lg text-primary">
            <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars((string) $card['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?php if ($can('admin.stats.view') || $can('admin.dashboard.view')): ?>
    <div class="lg:col-span-2 bg-white p-6 rounded shadow-sm border border-slate-100">
        <div class="flex items-center justify-between mb-6">
            <h4 class="font-bold text-slate-800">User Growth</h4>
            <select class="text-sm border-slate-200 rounded p-1 focus:ring-primary">
                <option>Last 7 days</option>
                <option>Last 30 days</option>
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
            <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
        </div>
    </div>
    <?php endif; ?>
    <div class="bg-white p-6 rounded shadow-sm border border-slate-100">
        <h4 class="font-bold text-slate-800 mb-6">Quick Actions</h4>
        <div class="space-y-3">
            <?php foreach ($quickActions as $action): ?>
            <?php if (!$canAny($action['permissions'])) { continue; } ?>
            <button class="w-full flex items-center gap-3 p-4 rounded bg-background-light hover:bg-slate-100 transition-colors text-left" type="button">
                <span class="material-symbols-outlined text-primary"><?= htmlspecialchars((string) $action['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                <div>
                    <p class="text-sm font-semibold"><?= htmlspecialchars((string) $action['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars((string) $action['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($can('admin.recipes.manage') || $can('admin.recipes.review')): ?>
<div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h4 class="font-bold text-slate-800">Recent Recipes</h4>
        <button class="text-primary text-sm font-semibold hover:underline" type="button">View All</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-background-light text-slate-500">
            <tr>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Recipe Title</th>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Author</th>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Date</th>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Status</th>
                <th class="px-6 py-4 font-semibold uppercase text-[11px] tracking-wider">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <tr>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded bg-slate-200 overflow-hidden" style="background-image: url('https://images.unsplash.com/photo-1464306076886-da185f6a7800?auto=format&fit=crop&w=120&q=80');"></div>
                        <span class="font-medium">Honey Glazed Salmon</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-600">Gordon R.</td>
                <td class="px-6 py-4 text-slate-600">Oct 24, 2023</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">Published</span>
                </td>
                <td class="px-6 py-4">
                    <button class="text-slate-400 hover:text-primary" type="button"><span class="material-symbols-outlined">edit</span></button>
                </td>
            </tr>
            <tr>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded bg-slate-200 overflow-hidden" style="background-image: url('https://images.unsplash.com/photo-1506354666786-959d6d497f1a?auto=format&fit=crop&w=120&q=80');"></div>
                        <span class="font-medium">Classic Pepperoni Pizza</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-600">Maria Rossi</td>
                <td class="px-6 py-4 text-slate-600">Oct 23, 2023</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full">Pending</span>
                </td>
                <td class="px-6 py-4">
                    <button class="text-slate-400 hover:text-primary" type="button"><span class="material-symbols-outlined">edit</span></button>
                </td>
            </tr>
            <tr>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded bg-slate-200 overflow-hidden" style="background-image: url('https://images.unsplash.com/photo-1481391032119-d89fee407e44?auto=format&fit=crop&w=120&q=80');"></div>
                        <span class="font-medium">Chocolate Lava Cake</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-slate-600">Sam Baker</td>
                <td class="px-6 py-4 text-slate-600">Oct 22, 2023</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">Published</span>
                </td>
                <td class="px-6 py-4">
                    <button class="text-slate-400 hover:text-primary" type="button"><span class="material-symbols-outlined">edit</span></button>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($can('admin.users.view') || $can('admin.users.manage') || $can('admin.users.role.assign')): ?>
<div class="bg-white rounded shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <h4 class="font-bold text-slate-800">New Registered Users</h4>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-slate-100">
        <div class="p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 overflow-hidden" style="background-image: url('https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80');"></div>
            <div>
                <p class="font-bold text-sm">John Doe</p>
                <p class="text-xs text-slate-500">Joined 2h ago</p>
            </div>
        </div>
        <div class="p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 overflow-hidden" style="background-image: url('https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=120&q=80');"></div>
            <div>
                <p class="font-bold text-sm">Jane Smith</p>
                <p class="text-xs text-slate-500">Joined 5h ago</p>
            </div>
        </div>
        <div class="p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 overflow-hidden" style="background-image: url('https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=120&q=80');"></div>
            <div>
                <p class="font-bold text-sm">Mike Wilson</p>
                <p class="text-xs text-slate-500">Joined Yesterday</p>
            </div>
        </div>
        <div class="p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 overflow-hidden" style="background-image: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80');"></div>
            <div>
                <p class="font-bold text-sm">Sarah Connor</p>
                <p class="text-xs text-slate-500">Joined Oct 23</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
