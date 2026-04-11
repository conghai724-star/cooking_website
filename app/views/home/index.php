<?php
$recipes = is_array($recipes ?? null) ? $recipes : [];
$featured = is_array($featured ?? null) ? $featured : array_slice($recipes, 0, 6);
$banner = is_array($banner ?? null) ? $banner : null;
$recipeOfDay = is_array($recipeOfDay ?? null) ? $recipeOfDay : null;
$todayMealPlans = is_array($todayMealPlans ?? null) ? $todayMealPlans : [];

$mealRows = [
    'breakfast' => ['label' => 'Sáng', 'icon' => 'breakfast_dining', 'icon_class' => 'text-orange-600'],
    'lunch' => ['label' => 'Trưa', 'icon' => 'lunch_dining', 'icon_class' => 'text-green-600'],
    'dinner' => ['label' => 'Tối', 'icon' => 'dinner_dining', 'icon_class' => 'text-blue-600'],
];
$dishRoleLabels = [
    'main' => 'Món chính',
    'side' => 'Món phụ',
    'soup' => 'Canh',
    'dessert' => 'Tráng miệng',
    'drink' => 'Đồ uống',
    'other' => 'Khác',
];
$todayPlanMap = [];
foreach ($todayMealPlans as $planItem) {
    $mealType = (string) ($planItem['meal_type'] ?? '');
    if (!isset($mealRows[$mealType])) {
        continue;
    }
    $todayPlanMap[$mealType][] = $planItem;
}
$heroTitle = trim((string) ($banner['title'] ?? '')) !== '' ? (string) $banner['title'] : 'Món ngon mỗi ngày';
$heroSubtitle = trim((string) ($banner['subtitle'] ?? '')) !== '' ? (string) $banner['subtitle'] : 'tìm kiếm công thức phù hợp với bạn';
$heroCtaText = trim((string) ($banner['cta_text'] ?? '')) !== '' ? (string) $banner['cta_text'] : 'Xem công thức';
$heroCtaUrl = trim((string) ($banner['cta_url'] ?? '')) !== '' ? (string) $banner['cta_url'] : '/recipes';
$heroBg = trim((string) ($banner['image_url'] ?? ''));
$heroHref = (str_starts_with($heroCtaUrl, 'http://') || str_starts_with($heroCtaUrl, 'https://'))
    ? $heroCtaUrl
    : (URLROOT . '/' . ltrim($heroCtaUrl, '/'));
$heroBgSrc = (str_starts_with($heroBg, 'http://') || str_starts_with($heroBg, 'https://'))
    ? $heroBg
    : ($heroBg !== '' ? (URLROOT . '/' . ltrim($heroBg, '/')) : '');

$difficultyLabels = [
    'easy' => 'Dễ',
    'medium' => 'Trung bình',
    'hard' => 'Khó',
];
?>

<section class="w-full space-y-8">
    <div class="relative overflow-hidden rounded-2xl border border-primary/10 p-8 text-white shadow-lg <?= $heroBg === '' ? 'bg-gradient-to-r from-[#2f1d13] via-[#5a2f18] to-[#9b4500]' : ''; ?>">
        <?php if ($heroBgSrc !== ''): ?>
            <img src="<?= htmlspecialchars($heroBgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Home Banner" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-black/45"></div>
        <?php endif; ?>
        <div class="absolute -right-24 -top-24 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-16 h-64 w-64 rounded-full bg-amber-200/10 blur-3xl"></div>
        <div class="relative z-10 max-w-3xl">
            <h1 class="text-3xl font-black leading-tight md:text-5xl"><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="mt-3 text-sm text-white/85 md:text-base">
                <?= htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a href="<?= htmlspecialchars($heroHref, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-[#9b4500] transition hover:bg-amber-50"><?= htmlspecialchars($heroCtaText, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php if (is_logged_in()): ?>
                    <a href="<?= URLROOT; ?>/meal-plans" class="rounded-xl border border-white/40 bg-white/10 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white/20">Mở Meal Plan</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-8 xl:grid-cols-12">
        <div class="space-y-6 xl:col-span-8">
            <div class="rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
                <form id="home-search-form" method="get" action="#" class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto]">
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary">search</span>
                        <input id="home-search-input" name="q" type="search" placeholder="Tìm món ăn phù hợp..." class="h-12 w-full rounded-xl border-none bg-slate-50 px-12 text-sm ring-1 ring-primary/10 focus:ring-2 focus:ring-primary">
                    </div>
                    <!-- <button type="submit" class="h-12 rounded-xl bg-primary px-5 text-sm font-bold text-white hover:opacity-90">Tìm</button> -->
                </form>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" data-home-filter="all" class="home-filter-chip rounded-full bg-primary px-4 py-1.5 text-[11px] font-bold tracking-wide text-white">Tất cả</button>
                    <button type="button" data-home-filter="difficulty:easy" class="home-filter-chip rounded-full bg-amber-50 px-4 py-1.5 text-[11px] font-bold tracking-wide text-primary">Dễ</button>
                    <button type="button" data-home-filter="max_time:30" class="home-filter-chip rounded-full bg-amber-50 px-4 py-1.5 text-[11px] font-bold tracking-wide text-primary">Dưới 30 phút</button>
                    <button type="button" data-home-filter="healthy:1" class="home-filter-chip rounded-full bg-amber-50 px-4 py-1.5 text-[11px] font-bold tracking-wide text-primary">Lành mạnh</button>
                </div>
            </div>

            <?php if ($featured === []): ?>
                <div class="rounded-xl border border-primary/10 bg-white p-6 text-slate-600">Không tìm thấy công thức nào</div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3" id="home-featured-recipes">
                    <?php foreach ($featured as $recipe): ?>
                        <?php
                        $recipeDifficulty = strtolower((string) ($recipe['difficulty'] ?? ''));
                        $recipeCookingTime = max(0, (int) ($recipe['cooking_time'] ?? 0));
                        $recipeTags = array_filter(array_map('trim', explode(',', strtolower((string) ($recipe['tag_slugs'] ?? '')))));
                        $recipeHealthy = in_array('healthy', $recipeTags, true) ? '1' : '0';
                        ?>
                        <article class="group overflow-hidden rounded-2xl border border-primary/10 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl"
                                 data-home-difficulty="<?= htmlspecialchars($recipeDifficulty, ENT_QUOTES, 'UTF-8'); ?>"
                                 data-home-max-time="<?= htmlspecialchars((string) $recipeCookingTime, ENT_QUOTES, 'UTF-8'); ?>"
                                 data-home-healthy="<?= htmlspecialchars($recipeHealthy, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="relative aspect-[4/5] overflow-hidden">
                                <?php if (!empty($recipe['image'])): ?>
                                    <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105" src="<?= URLROOT; ?>/uploads/<?= htmlspecialchars((string) $recipe['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars((string) $recipe['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php else: ?>
                                    <div class="h-full w-full bg-gradient-to-br from-amber-200 to-orange-300"></div>
                                <?php endif; ?>
                                <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-primary">
                                    <?= htmlspecialchars($difficultyLabels[strtolower((string) ($recipe['difficulty'] ?? ''))] ?? (string) ($recipe['difficulty'] ?? 'Dễ'), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                            <div class="space-y-3 p-4">
                                <h3 class="line-clamp-2 text-base font-bold leading-tight text-slate-900 transition group-hover:text-primary">
                                    <a href="<?= URLROOT; ?>/recipes/<?= (int) $recipe['id']; ?>">
                                        <?= htmlspecialchars((string) $recipe['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>
                                <p class="line-clamp-2 text-xs text-slate-500">
                                    <?= htmlspecialchars(mb_substr((string) ($recipe['description'] ?? ''), 0, 100), ENT_QUOTES, 'UTF-8'); ?>...
                                </p>
                                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                                    <span class="text-xs font-semibold text-slate-700"><?= htmlspecialchars((string) ($recipe['author_name'] ?? 'Ẩn danh'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <a href="<?= URLROOT; ?>/recipes/<?= (int) $recipe['id']; ?>" class="text-xs font-bold text-primary">Xem chi tiết</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div id="home-featured-empty" class="rounded-xl border border-primary/10 bg-white p-6 text-slate-600 hidden">Không tìm thấy công thức phù hợp với bộ lọc.</div>
            <?php endif; ?>
            <script>
                (function() {
                    const filterButtons = document.querySelectorAll('.home-filter-chip');
                    const cards = document.querySelectorAll('#home-featured-recipes article');
                    const emptyMessage = document.getElementById('home-featured-empty');
                    const searchForm = document.getElementById('home-search-form');
                    const searchInput = document.getElementById('home-search-input');

                    const state = {
                        type: 'all',
                        value: null,
                        query: ''
                    };

                    const setButtonState = (activeButton) => {
                        filterButtons.forEach(button => {
                            button.classList.remove('bg-primary', 'text-white');
                            button.classList.add('bg-amber-50', 'text-primary');
                        });
                        if (activeButton) {
                            activeButton.classList.add('bg-primary', 'text-white');
                            activeButton.classList.remove('bg-amber-50', 'text-primary');
                        }
                    };

                    const matchesQuery = (card, query) => {
                        if (!query) {
                            return true;
                        }
                        const title = card.querySelector('h3')?.textContent.trim().toLowerCase() || '';
                        const description = card.querySelector('p')?.textContent.trim().toLowerCase() || '';
                        return title.includes(query) || description.includes(query);
                    };

                    const updateVisibility = () => {
                        let visibleCount = 0;
                        cards.forEach(card => {
                            const difficulty = (card.dataset.homeDifficulty || '').toLowerCase();
                            const maxTime = parseInt(card.dataset.homeMaxTime || '0', 10);
                            const healthy = card.dataset.homeHealthy === '1';
                            let visible = true;

                            if (state.type === 'difficulty') {
                                visible = difficulty === state.value;
                            } else if (state.type === 'max_time') {
                                visible = maxTime > 0 && maxTime <= state.value;
                            } else if (state.type === 'healthy') {
                                visible = healthy;
                            }

                            if (visible && state.query) {
                                visible = matchesQuery(card, state.query);
                            }

                            card.style.display = visible ? '' : 'none';
                            if (visible) {
                                visibleCount += 1;
                            }
                        });
                        if (emptyMessage) {
                            emptyMessage.classList.toggle('hidden', visibleCount > 0);
                        }
                    };

                    filterButtons.forEach(button => {
                        button.addEventListener('click', () => {
                            setButtonState(button);
                            const filterValue = button.dataset.homeFilter || 'all';
                            if (filterValue === 'all') {
                                state.type = 'all';
                                state.value = null;
                            } else {
                                const [type, rawValue] = filterValue.split(':');
                                state.type = type;
                                state.value = parseInt(rawValue, 10);
                            }
                            updateVisibility();
                        });
                    });

                    if (searchForm) {
                        searchForm.addEventListener('submit', (event) => {
                            event.preventDefault();
                            state.query = searchInput?.value.trim().toLowerCase() || '';
                            updateVisibility();
                        });
                    }
                })();
            </script>
        </div>

        <aside class="space-y-6 xl:col-span-4">
            <?php if ($recipeOfDay !== null): ?>
                <div class="rounded-2xl border border-primary/20 bg-amber-50 p-5 shadow-sm">
                    <h2 class="mb-3 text-base font-black tracking-tight text-primary">Công thức hôm nay</h2>
                    <a href="<?= URLROOT; ?>/recipes/<?= (int) $recipeOfDay['id']; ?>" class="block">
                        <p class="text-sm font-bold text-slate-900"><?= htmlspecialchars((string) ($recipeOfDay['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="mt-1 text-[11px] text-slate-600">Bởi <?= htmlspecialchars((string) ($recipeOfDay['author_name'] ?? 'Ẩn danh'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </a>
                </div>
            <?php endif; ?>

            <div class="rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-bold">Hôm nay bạn ăn gì?</h2>
                    <a href="<?= URLROOT; ?>/meal-plans" class="text-[11px] font-bold uppercase tracking-wider text-primary">Tùy chỉnh</a>
                </div>
                <div class="space-y-3">
                    <?php foreach ($mealRows as $mealType => $meta): ?>
                        <?php $items = $todayPlanMap[$mealType] ?? []; ?>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="mb-2 flex items-center gap-3">
                                <span class="material-symbols-outlined <?= htmlspecialchars($meta['icon_class'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($meta['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500"><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <?php if ($items === []): ?>
                                <p class="text-sm font-semibold text-slate-400">Chưa có món</p>
                            <?php else: ?>
                                <div class="space-y-1">
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                        $dishRole = (string) ($item['dish_role'] ?? 'main');
                                        $dishRoleLabel = $dishRoleLabels[$dishRole] ?? 'Khác';
                                        ?>
                                        <a href="<?= URLROOT; ?>/recipes/<?= (int) ($item['recipe_id'] ?? 0); ?>" class="flex items-center justify-between gap-2 text-sm font-semibold text-slate-800 hover:text-primary">
                                            <span class="line-clamp-1"><?= htmlspecialchars((string) ($item['title'] ?? 'Công thức'), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700"><?= htmlspecialchars($dishRoleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-bold">Gợi ý theo dõi</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">Thảo Nguyên</p>
                            <p class="text-[11px] text-slate-500">Blogger ẩm thực</p>
                        </div>
                        <button class="rounded-full border border-primary px-3 py-1 text-xs font-bold text-primary">Follow</button>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">Hoàng Bách</p>
                            <p class="text-[11px] text-slate-500">Đầu bếp tại gia</p>
                        </div>
                        <button class="rounded-full border border-primary px-3 py-1 text-xs font-bold text-primary">Follow</button>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-bold">Công thức nổi bật</h2>
                <div class="space-y-4">
                    <?php foreach (array_slice($recipes, 0, 3) as $recipe): ?>
                        <a href="<?= URLROOT; ?>/recipes/<?= (int) $recipe['id']; ?>" class="group flex gap-3">
                            <?php if (!empty($recipe['image'])): ?>
                                <img class="h-16 w-16 rounded-lg object-cover" src="<?= URLROOT; ?>/uploads/<?= htmlspecialchars((string) $recipe['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars((string) $recipe['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                <div class="h-16 w-16 rounded-lg bg-gradient-to-br from-amber-200 to-orange-300"></div>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <p class="line-clamp-1 text-sm font-bold transition group-hover:text-primary"><?= htmlspecialchars((string) $recipe['title'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="mt-1 text-[11px] text-slate-500"><?= htmlspecialchars((string) ($recipe['author_name'] ?? 'Ẩn danh'), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
</section>
