<?php
$user = is_array($user ?? null) ? $user : [];
$recipes = is_array($recipes ?? null) ? $recipes : [];
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];
$tips = is_array($tips ?? null) ? $tips : [];
$savedIngredients = is_array($saved_ingredients ?? null) ? $saved_ingredients : [];
$savedTips = is_array($saved_tips ?? null) ? $saved_tips : [];

$isOwner = (bool) ($is_owner ?? false);
$isFollowing = (bool) ($is_following ?? false);
$isLoggedIn = (bool) ($is_logged_in ?? false);
$isBlockedByViewer = (bool) ($is_blocked_by_viewer ?? false);
$isViewerBlocked = (bool) ($is_viewer_blocked ?? false);
$notice = (string) ($notice ?? '');

$followerCount = (int) ($follower_count ?? 0);
$followingCount = (int) ($following_count ?? 0);

$profileUserId = (int) ($user['id'] ?? 0);
$displayName = trim((string) ($user['name'] ?? '�?u b?p'));
if ($displayName === '') {
    $displayName = '�?u b?p';
}

$username = trim((string) ($user['username'] ?? ''));
$handle = $username !== '' ? '@' . $username : '@thanhvien';

$bio = trim((string) ($user['bio'] ?? ''));
if ($bio === '') {
    $bio = 'Y�u n?u an v� chia s? n?i dung m?i ng�y.';
}

$avatar = trim((string) ($user['avatar'] ?? ''));
$avatarUrl = $avatar !== '' ? URLROOT . '/uploads/' . rawurlencode($avatar) : '';

$noticeText = match ($notice) {
    'report_user_success' => '�� g?i b�o c�o t�i kho?n.',
    'report_user_exists' => 'B?n d� b�o c�o t�i kho?n n�y tru?c d�.',
    'report_reason_required' => 'Vui l�ng ch?n l� do b�o c�o.',
    'cannot_report_self' => 'B?n kh�ng th? b�o c�o ch�nh m�nh.',
    'block_user_success' => '�� ch?n t�i kho?n n�y.',
    'block_user_exists' => 'T�i kho?n n�y d� n?m trong danh s�ch ch?n.',
    'cannot_block_self' => 'B?n kh�ng th? t? ch?n ch�nh m�nh.',
    'user_action_failed' => 'Kh�ng th? th?c hi?n thao t�c n�y l�c n�y.',
    default => '',
};
?>

<section class="w-full">
    <div class="mx-auto w-full max-w-[960px] px-2 py-4 sm:px-4">
        <?php if ($noticeText !== ''): ?>
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="relative mb-20 w-full">
            <div class="h-48 w-full overflow-hidden rounded-xl bg-primary/10 md:h-64">
                <div class="h-full w-full bg-[radial-gradient(circle_at_15%_20%,rgba(255,255,255,.55)_0%,rgba(255,255,255,0)_38%),linear-gradient(125deg,#f59f0a,#f97316,#fb923c)]"></div>
            </div>

            <div class="absolute -bottom-16 left-4 flex w-[calc(100%-32px)] flex-col items-start gap-4 md:left-8 md:w-[calc(100%-64px)] md:flex-row md:items-end">
                <?php if ($avatarUrl !== ''): ?>
                    <img class="h-32 w-32 rounded-full border-4 border-white object-cover shadow-lg shadow-black/5 md:h-40 md:w-40" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="?nh d?i di?n">
                <?php else: ?>
                    <div class="flex h-32 w-32 items-center justify-center rounded-full border-4 border-white bg-primary/20 text-4xl font-bold text-primary shadow-lg shadow-black/5 md:h-40 md:w-40">
                        <?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="flex-1 pb-2">
                    <h1 class="text-2xl font-bold text-slate-900 md:text-3xl"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="font-medium text-slate-500"><?= htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'); ?> � Th�nh vi�n</p>
                </div>

                <div class="relative flex gap-2 pb-2">
                    <?php if ($isOwner): ?>
                        <a class="flex h-10 items-center justify-center rounded-lg border-2 border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/profile/edit">S?a h? so</a>
                        <a class="flex h-10 items-center justify-center rounded-lg bg-primary px-6 text-sm font-bold text-white hover:brightness-105" href="<?= URLROOT; ?>/recipes/create">�ang c�ng th?c</a>
                    <?php elseif ($isLoggedIn): ?>
                        <?php if ($isBlockedByViewer): ?>
                            <span class="flex h-10 items-center justify-center rounded-lg border-2 border-rose-200 bg-rose-50 px-6 text-sm font-bold text-rose-700">�� ch?n</span>
                        <?php elseif ($isViewerBlocked): ?>
                            <span class="flex h-10 items-center justify-center rounded-lg border-2 border-slate-200 bg-slate-100 px-6 text-sm font-bold text-slate-500">B?n b? ch?n</span>
                        <?php else: ?>
                            <?php if ($isFollowing): ?>
                                <form method="post" action="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/unfollow">
                                    <?= csrf_field(); ?>
                                    <button class="flex h-10 items-center justify-center rounded-lg border-2 border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 hover:bg-slate-50" type="submit">H?y theo d�i</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/follow">
                                    <?= csrf_field(); ?>
                                    <button class="flex h-10 items-center justify-center rounded-lg bg-primary px-6 text-sm font-bold text-white hover:brightness-105" type="submit">Theo d�i</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                        <details class="relative">
                            <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg border-2 border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                                <span class="material-symbols-outlined text-[20px]">more_horiz</span>
                            </summary>
                            <div class="absolute right-0 z-30 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
                                <?php if (!$isBlockedByViewer && !$isViewerBlocked): ?>
                                    <form method="post" action="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/report" class="mb-2 space-y-2 border-b border-slate-100 pb-3">
                                        <?= csrf_field(); ?>
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">B�o c�o t�i kho?n</label>
                                        <select name="reason" required class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                            <option value="">Ch?n l� do</option>
                                            <option value="M?o danh">M?o danh</option>
                                            <option value="Spam">Spam</option>
                                            <option value="N?i dung vi ph?m">N?i dung vi ph?m</option>
                                        </select>
                                        <textarea name="details" rows="2" maxlength="1000" placeholder="Chi ti?t (kh�ng b?t bu?c)" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></textarea>
                                        <button class="w-full rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50" type="submit">G?i b�o c�o</button>
                                    </form>

                                    <form method="post" action="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/block" class="mb-2 border-b border-slate-100 pb-3" data-confirm="B?n ch?c ch?n mu?n ch?n t�i kho?n n�y?">
                                        <?= csrf_field(); ?>
                                        <button class="w-full rounded-lg border border-rose-300 px-3 py-1.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Ch?n t�i kho?n</button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50" data-copy-link-btn data-link="<?= htmlspecialchars(URLROOT . '/users/' . $profileUserId, ENT_QUOTES, 'UTF-8'); ?>">Sao ch�p li�n k?t h? so</button>
                            </div>
                        </details>
                    <?php else: ?>
                        <a class="flex h-10 items-center justify-center rounded-lg bg-primary px-6 text-sm font-bold text-white" href="<?= URLROOT; ?>/login">�ang nh?p d? theo d�i</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-12">
            <div class="md:col-span-8">
                <h3 class="mb-2 text-lg font-bold">Gi?i thi?u</h3>
                <p class="text-base leading-relaxed text-slate-600"><?= htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="flex justify-between gap-6 border-t pt-4 md:col-span-4 md:justify-end md:border-t-0 md:pt-0">
                <div class="flex flex-col items-center">
                    <span class="text-2xl font-bold text-slate-900"><?= count($recipes); ?></span>
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">C�ng th?c</span>
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-2xl font-bold text-slate-900"><?= $followerCount; ?></span>
                    <a class="text-xs font-medium uppercase tracking-wider text-slate-400 hover:text-primary" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/followers">Theo d�i b?n</a>
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-2xl font-bold text-slate-900"><?= $followingCount; ?></span>
                    <a class="text-xs font-medium uppercase tracking-wider text-slate-400 hover:text-primary" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/following">B?n theo d�i</a>
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-sm font-bold text-slate-900">Xem</span>
                    <a class="text-xs font-medium uppercase tracking-wider text-slate-400 hover:text-primary" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/meal-plans">K? ho?ch</a>
                </div>
            </div>
        </div>

        <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200">
            <button class="profile-tab border-b-2 border-primary px-6 py-4 text-sm font-bold text-primary" type="button" data-tab="recipes">C�ng th?c c?a t�i</button>
            <button class="profile-tab border-b-2 border-transparent px-6 py-4 text-sm font-bold text-slate-500" type="button" data-tab="ingredients">Nguy�n li?u</button>
            <button class="profile-tab border-b-2 border-transparent px-6 py-4 text-sm font-bold text-slate-500" type="button" data-tab="tips">M?o v?t</button>
            <button class="profile-tab border-b-2 border-transparent px-6 py-4 text-sm font-bold text-slate-500" type="button" data-tab="plans">K? ho?ch</button>
        </div>

        <div class="profile-pane" data-pane="recipes">
            <?php if (!empty($recipes)): ?>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($recipes as $recipe): ?>
                        <?php
                        $thumb = trim((string) ($recipe['image'] ?? ''));
                        if ($thumb !== '' && !preg_match('/^https?:\\/\\//i', $thumb)) {
                            $thumb = URLROOT . '/uploads/' . ltrim($thumb, '/');
                        }
                        ?>
                        <a href="<?= URLROOT; ?>/recipes/<?= (int) ($recipe['id'] ?? 0); ?>" class="overflow-hidden rounded-xl border border-slate-200 bg-white hover:border-primary">
                            <?php if ($thumb !== ''): ?>
                                <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="?nh c�ng th?c">
                            <?php endif; ?>
                            <div class="p-4">
                                <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($recipe['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($recipe['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Chua c� c�ng th?c n�o.</div>
            <?php endif; ?>
        </div>

        <div class="profile-pane hidden" data-pane="ingredients">
            <div class="mb-4 flex gap-2 border-b border-slate-200">
                <button class="profile-subtab border-b-2 border-primary px-4 py-2 text-sm font-bold text-primary" type="button" data-subtab-group="ingredients" data-subtab="mine">C?a t�i</button>
                <?php if ($isOwner): ?>
                    <button class="profile-subtab border-b-2 border-transparent px-4 py-2 text-sm font-bold text-slate-500" type="button" data-subtab-group="ingredients" data-subtab="saved">�� luu</button>
                <?php endif; ?>
            </div>

            <div class="profile-subpane" data-subpane-group="ingredients" data-subpane="mine">
                <?php if (!empty($ingredients)): ?>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($ingredients as $ingredient): ?>
                            <?php
                            $thumb = trim((string) ($ingredient['image'] ?? ''));
                            if ($thumb !== '' && !preg_match('/^https?:\\/\\//i', $thumb)) {
                                $thumb = URLROOT . '/uploads/' . ltrim($thumb, '/');
                            }
                            ?>
                            <a href="<?= URLROOT; ?>/ingredients/<?= (int) ($ingredient['id'] ?? 0); ?>" class="overflow-hidden rounded-xl border border-slate-200 bg-white hover:border-primary">
                                <?php if ($thumb !== ''): ?>
                                    <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="?nh nguy�n li?u">
                                <?php endif; ?>
                                <div class="p-4">
                                    <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($ingredient['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($ingredient['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Chua c� nguy�n li?u n�o.</div>
                <?php endif; ?>
            </div>

            <?php if ($isOwner): ?>
                <div class="profile-subpane hidden" data-subpane-group="ingredients" data-subpane="saved">
                    <?php if (!empty($savedIngredients)): ?>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($savedIngredients as $ingredient): ?>
                                <?php
                                $thumb = trim((string) ($ingredient['image'] ?? ''));
                                if ($thumb !== '' && !preg_match('/^https?:\\/\\//i', $thumb)) {
                                    $thumb = URLROOT . '/uploads/' . ltrim($thumb, '/');
                                }
                                ?>
                                <a href="<?= URLROOT; ?>/ingredients/<?= (int) ($ingredient['id'] ?? 0); ?>" class="overflow-hidden rounded-xl border border-slate-200 bg-white hover:border-primary">
                                    <?php if ($thumb !== ''): ?>
                                        <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="?nh nguy�n li?u">
                                    <?php endif; ?>
                                    <div class="p-4">
                                        <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($ingredient['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($ingredient['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">B?n chua luu nguy�n li?u n�o.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-pane hidden" data-pane="tips">
            <div class="mb-4 flex gap-2 border-b border-slate-200">
                <button class="profile-subtab border-b-2 border-primary px-4 py-2 text-sm font-bold text-primary" type="button" data-subtab-group="tips" data-subtab="mine">C?a t�i</button>
                <?php if ($isOwner): ?>
                    <button class="profile-subtab border-b-2 border-transparent px-4 py-2 text-sm font-bold text-slate-500" type="button" data-subtab-group="tips" data-subtab="saved">�� luu</button>
                <?php endif; ?>
            </div>

            <div class="profile-subpane" data-subpane-group="tips" data-subpane="mine">
                <?php if (!empty($tips)): ?>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($tips as $tip): ?>
                            <?php
                            $thumb = trim((string) ($tip['cover_image'] ?? ''));
                            if ($thumb !== '' && !preg_match('/^https?:\\/\\//i', $thumb)) {
                                $thumb = URLROOT . '/uploads/' . ltrim($thumb, '/');
                            }
                            ?>
                            <a href="<?= URLROOT; ?>/tips/<?= htmlspecialchars((string) ($tip['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="overflow-hidden rounded-xl border border-slate-200 bg-white hover:border-primary">
                                <?php if ($thumb !== ''): ?>
                                    <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="?nh m?o v?t">
                                <?php endif; ?>
                                <div class="p-4">
                                    <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($tip['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($tip['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Chua c� m?o v?t n�o.</div>
                <?php endif; ?>
            </div>

            <?php if ($isOwner): ?>
                <div class="profile-subpane hidden" data-subpane-group="tips" data-subpane="saved">
                    <?php if (!empty($savedTips)): ?>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($savedTips as $tip): ?>
                                <?php
                                $thumb = trim((string) ($tip['cover_image'] ?? ''));
                                if ($thumb !== '' && !preg_match('/^https?:\\/\\//i', $thumb)) {
                                    $thumb = URLROOT . '/uploads/' . ltrim($thumb, '/');
                                }
                                ?>
                                <a href="<?= URLROOT; ?>/tips/<?= htmlspecialchars((string) ($tip['slug'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="overflow-hidden rounded-xl border border-slate-200 bg-white hover:border-primary">
                                    <?php if ($thumb !== ''): ?>
                                        <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="?nh m?o v?t">
                                    <?php endif; ?>
                                    <div class="p-4">
                                        <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($tip['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($tip['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">B?n chua luu m?o v?t n�o.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-pane hidden" data-pane="plans">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h4 class="mb-2 text-lg font-bold text-slate-900">K? ho?ch b?a an</h4>
                <p class="mb-4 text-sm text-slate-600">
                    <?= $isOwner ? 'Qu?n l� k? ho?ch an theo ng�y/tu?n c?a b?n.' : 'Xem k? ho?ch an du?c chia s? c?a ngu?i d�ng n�y.'; ?>
                </p>
                <a class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2 text-sm font-bold text-white hover:brightness-105" href="<?= $isOwner ? (URLROOT . '/meal-plans') : (URLROOT . '/users/' . $profileUserId . '/meal-plans'); ?>">
                    <?= $isOwner ? 'M? k? ho?ch c?a t�i' : 'Xem k? ho?ch c?a ngu?i d�ng'; ?>
                </a>
            </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.profile-tab').forEach(function (tabBtn) {
    tabBtn.addEventListener('click', function () {
        var tab = this.getAttribute('data-tab');
        document.querySelectorAll('.profile-tab').forEach(function (btn) {
            btn.classList.remove('border-primary', 'text-primary');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        this.classList.add('border-primary', 'text-primary');
        this.classList.remove('border-transparent', 'text-slate-500');

        document.querySelectorAll('.profile-pane').forEach(function (pane) {
            pane.classList.add('hidden');
        });
        var activePane = document.querySelector('.profile-pane[data-pane="' + tab + '"]');
        if (activePane) activePane.classList.remove('hidden');
    });
});

document.querySelectorAll('.profile-subtab').forEach(function (subBtn) {
    subBtn.addEventListener('click', function () {
        var group = this.getAttribute('data-subtab-group');
        var tab = this.getAttribute('data-subtab');
        document.querySelectorAll('.profile-subtab[data-subtab-group="' + group + '"]').forEach(function (btn) {
            btn.classList.remove('border-primary', 'text-primary');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        this.classList.add('border-primary', 'text-primary');
        this.classList.remove('border-transparent', 'text-slate-500');

        document.querySelectorAll('.profile-subpane[data-subpane-group="' + group + '"]').forEach(function (pane) {
            pane.classList.add('hidden');
        });
        var activePane = document.querySelector('.profile-subpane[data-subpane-group="' + group + '"][data-subpane="' + tab + '"]');
        if (activePane) activePane.classList.remove('hidden');
    });
});

document.querySelectorAll('[data-copy-link-btn]').forEach(function (btn) {
    btn.addEventListener('click', async function () {
        var link = this.getAttribute('data-link') || '';
        if (!link) return;
        try {
            await navigator.clipboard.writeText(link);
            this.textContent = '�� sao ch�p li�n k?t';
            setTimeout(() => { this.textContent = 'Sao ch�p li�n k?t h? so'; }, 1200);
        } catch (_) {}
    });
});
</script>
