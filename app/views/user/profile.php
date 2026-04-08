<?php
$user = is_array($user ?? null) ? $user : [];
$recipes = is_array($recipes ?? null) ? $recipes : [];
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];
$tips = is_array($tips ?? null) ? $tips : [];
$savedIngredients = is_array($saved_ingredients ?? null) ? $saved_ingredients : [];
$savedTips = is_array($saved_tips ?? null) ? $saved_tips : [];
$certificates = is_array($certificates ?? null) ? $certificates : [];

$isOwner = (bool) ($is_owner ?? false);
$isFollowing = (bool) ($is_following ?? false);
$isLoggedIn = (bool) ($is_logged_in ?? false);
$isBlockedByViewer = (bool) ($is_blocked_by_viewer ?? false);
$isViewerBlocked = (bool) ($is_viewer_blocked ?? false);
$notice = (string) ($notice ?? '');

$followerCount = (int) ($follower_count ?? 0);
$followingCount = (int) ($following_count ?? 0);
$certificateCount = (int) ($certificate_count ?? 0);

$profileUserId = (int) ($user['id'] ?? 0);
$displayName = trim((string) ($user['name'] ?? 'Đầu bếp'));
if ($displayName === '') {
    $displayName = 'Đầu bếp';
}

$username = trim((string) ($user['username'] ?? ''));
$handle = $username !== '' ? '@' . $username : '@thanhvien';

$bio = trim((string) ($user['bio'] ?? ''));
if ($bio === '') {
    $bio = 'Yêu nấu ăn và chia sẻ nội dung mới mỗi ngày.';
}

$avatar = trim((string) ($user['avatar'] ?? ''));
$avatarUrl = $avatar !== '' ? URLROOT . '/uploads/' . rawurlencode($avatar) : '';

$noticeText = match ($notice) {
    'report_user_success' => 'Đã gửi báo cáo tài khoản.',
    'report_user_exists' => 'Bạn đã báo cáo tài khoản này trước đó.',
    'report_reason_required' => 'Vui lòng chọn lý do báo cáo.',
    'cannot_report_self' => 'Bạn không thể báo cáo chính mình.',
    'block_user_success' => 'Đã chặn tài khoản này.',
    'block_user_exists' => 'Tài khoản này đã nằm trong danh sách chặn.',
    'cannot_block_self' => 'Bạn không thể tự chặn chính mình.',
    'user_action_failed' => 'Không thể thực hiện thao tác này lúc này.',
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
                    <img class="h-32 w-32 rounded-full border-4 border-white object-cover shadow-lg shadow-black/5 md:h-40 md:w-40" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Ảnh đại diện">
                <?php else: ?>
                    <div class="flex h-32 w-32 items-center justify-center rounded-full border-4 border-white bg-primary/20 text-4xl font-bold text-primary shadow-lg shadow-black/5 md:h-40 md:w-40">
                        <?= htmlspecialchars(strtoupper(substr($displayName, 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="flex-1 pb-2">
                    <h1 class="text-2xl font-bold text-slate-900 md:text-3xl"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="font-medium text-slate-500"><?= htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'); ?> • Thành viên</p>
                </div>

                <div class="relative flex gap-2 pb-2">
                    <?php if ($isOwner): ?>
                        <a class="flex h-10 items-center justify-center rounded-lg border-2 border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 hover:bg-slate-50" href="<?= URLROOT; ?>/profile/edit">Sửa hồ sơ</a>
                        <a class="flex h-10 items-center justify-center rounded-lg bg-primary px-6 text-sm font-bold text-white hover:brightness-105" href="<?= URLROOT; ?>/recipes/create">Đăng công thức</a>
                    <?php elseif ($isLoggedIn): ?>
                        <?php if ($isBlockedByViewer): ?>
                            <span class="flex h-10 items-center justify-center rounded-lg border-2 border-rose-200 bg-rose-50 px-6 text-sm font-bold text-rose-700">Đã chặn</span>
                        <?php elseif ($isViewerBlocked): ?>
                            <span class="flex h-10 items-center justify-center rounded-lg border-2 border-slate-200 bg-slate-100 px-6 text-sm font-bold text-slate-500">Bạn bị chặn</span>
                        <?php else: ?>
                            <?php if ($isFollowing): ?>
                                <form method="post" action="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/unfollow">
                                    <?= csrf_field(); ?>
                                    <button class="flex h-10 items-center justify-center rounded-lg border-2 border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 hover:bg-slate-50" type="submit">Hủy theo dõi</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/follow">
                                    <?= csrf_field(); ?>
                                    <button class="flex h-10 items-center justify-center rounded-lg bg-primary px-6 text-sm font-bold text-white hover:brightness-105" type="submit">Theo dõi</button>
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
                                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Báo cáo tài khoản</label>
                                        <select name="reason" required class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                                            <option value="">Chọn lý do</option>
                                            <option value="Mạo danh">Mạo danh</option>
                                            <option value="Spam">Spam</option>
                                            <option value="Nội dung vi phạm">Nội dung vi phạm</option>
                                        </select>
                                        <textarea name="details" rows="2" maxlength="1000" placeholder="Chi tiết (không bắt buộc)" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></textarea>
                                        <button class="w-full rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50" type="submit">Gửi báo cáo</button>
                                    </form>

                                    <form method="post" action="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/block" class="mb-2 border-b border-slate-100 pb-3" data-confirm="Bạn chắc chắn muốn chặn tài khoản này?">
                                        <?= csrf_field(); ?>
                                        <button class="w-full rounded-lg border border-rose-300 px-3 py-1.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50" type="submit">Chặn tài khoản</button>
                                    </form>
                                <?php endif; ?>

                                <button type="button" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50" data-copy-link-btn data-link="<?= htmlspecialchars(URLROOT . '/users/' . $profileUserId, ENT_QUOTES, 'UTF-8'); ?>">Sao chép liên kết hồ sơ</button>
                            </div>
                        </details>
                    <?php else: ?>
                        <a class="flex h-10 items-center justify-center rounded-lg bg-primary px-6 text-sm font-bold text-white" href="<?= URLROOT; ?>/login">Đăng nhập để theo dõi</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-12">
            <div class="md:col-span-8">
                <h3 class="mb-2 text-lg font-bold">Giới thiệu</h3>
                <p class="text-base leading-relaxed text-slate-600"><?= htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="flex justify-between gap-6 border-t pt-4 md:col-span-4 md:justify-end md:border-t-0 md:pt-0">
                <div class="flex flex-col items-center">
                    <span class="text-2xl font-bold text-slate-900"><?= count($recipes); ?></span>
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Công thức</span>
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-2xl font-bold text-slate-900"><?= $followerCount; ?></span>
                    <a class="text-xs font-medium uppercase tracking-wider text-slate-400 hover:text-primary" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/followers">Theo dõi bạn</a>
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-2xl font-bold text-slate-900"><?= $followingCount; ?></span>
                    <a class="text-xs font-medium uppercase tracking-wider text-slate-400 hover:text-primary" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/following">Bạn theo dõi</a>
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-sm font-bold text-slate-900">Xem</span>
                    <a class="text-xs font-medium uppercase tracking-wider text-slate-400 hover:text-primary" href="<?= URLROOT; ?>/users/<?= $profileUserId; ?>/meal-plans">Kế hoạch</a>
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-2xl font-bold text-slate-900"><?= $certificateCount; ?></span>
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Chứng nhận</span>
                </div>
            </div>
        </div>

        <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200">
            <button class="profile-tab border-b-2 border-primary px-6 py-4 text-sm font-bold text-primary" type="button" data-tab="recipes">Công thức của tôi</button>
            <button class="profile-tab border-b-2 border-transparent px-6 py-4 text-sm font-bold text-slate-500" type="button" data-tab="ingredients">Nguyên liệu</button>
            <button class="profile-tab border-b-2 border-transparent px-6 py-4 text-sm font-bold text-slate-500" type="button" data-tab="tips">Mẹo vặt</button>
            <button class="profile-tab border-b-2 border-transparent px-6 py-4 text-sm font-bold text-slate-500" type="button" data-tab="plans">Kế hoạch</button>
            <button class="profile-tab border-b-2 border-transparent px-6 py-4 text-sm font-bold text-slate-500" type="button" data-tab="certificates">Chứng nhận</button>
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
                                <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="Ảnh công thức">
                            <?php endif; ?>
                            <div class="p-4">
                                <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($recipe['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($recipe['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Chưa có công thức nào.</div>
            <?php endif; ?>
        </div>

        <div class="profile-pane hidden" data-pane="ingredients">
            <div class="mb-4 flex gap-2 border-b border-slate-200">
                <button class="profile-subtab border-b-2 border-primary px-4 py-2 text-sm font-bold text-primary" type="button" data-subtab-group="ingredients" data-subtab="mine">Của tôi</button>
                <?php if ($isOwner): ?>
                    <button class="profile-subtab border-b-2 border-transparent px-4 py-2 text-sm font-bold text-slate-500" type="button" data-subtab-group="ingredients" data-subtab="saved">Đã lưu</button>
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
                                    <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="Ảnh nguyên liệu">
                                <?php endif; ?>
                                <div class="p-4">
                                    <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($ingredient['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($ingredient['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Chưa có nguyên liệu nào.</div>
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
                                        <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="Ảnh nguyên liệu">
                                    <?php endif; ?>
                                    <div class="p-4">
                                        <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($ingredient['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($ingredient['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Bạn chưa lưu nguyên liệu nào.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-pane hidden" data-pane="tips">
            <div class="mb-4 flex gap-2 border-b border-slate-200">
                <button class="profile-subtab border-b-2 border-primary px-4 py-2 text-sm font-bold text-primary" type="button" data-subtab-group="tips" data-subtab="mine">Của tôi</button>
                <?php if ($isOwner): ?>
                    <button class="profile-subtab border-b-2 border-transparent px-4 py-2 text-sm font-bold text-slate-500" type="button" data-subtab-group="tips" data-subtab="saved">Đã lưu</button>
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
                                    <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="Ảnh mẹo vặt">
                                <?php endif; ?>
                                <div class="p-4">
                                    <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($tip['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($tip['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Chưa có mẹo vặt nào.</div>
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
                                        <img class="h-40 w-full object-cover" src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>" alt="Ảnh mẹo vặt">
                                    <?php endif; ?>
                                    <div class="p-4">
                                        <h4 class="mb-2 font-bold text-slate-900"><?= htmlspecialchars((string) ($tip['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <p class="line-clamp-2 text-sm text-slate-500"><?= htmlspecialchars((string) ($tip['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Bạn chưa lưu mẹo vặt nào.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-pane hidden" data-pane="plans">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h4 class="mb-2 text-lg font-bold text-slate-900">Kế hoạch bữa ăn</h4>
                <p class="mb-4 text-sm text-slate-600">
                    <?= $isOwner ? 'Quản lý kế hoạch ăn theo ngày/tuần của bạn.' : 'Xem kế hoạch ăn được chia sẻ của người dùng này.'; ?>
                </p>
                <a class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-2 text-sm font-bold text-white hover:brightness-105" href="<?= $isOwner ? (URLROOT . '/meal-plans') : (URLROOT . '/users/' . $profileUserId . '/meal-plans'); ?>">
                    <?= $isOwner ? 'Mở kế hoạch của tôi' : 'Xem kế hoạch của người dùng'; ?>
                </a>
            </div>
        </div>

        <div class="profile-pane hidden" data-pane="certificates">
            <?php if (!empty($certificates)): ?>
                <div class="space-y-3">
                    <?php foreach ($certificates as $certificate): ?>
                        <article class="rounded-xl border border-slate-200 bg-white p-5">
                            <h4 class="text-lg font-bold text-slate-900"><?= htmlspecialchars((string) ($certificate['quiz_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="mt-1 text-sm text-slate-500">Chủ đề: <?= htmlspecialchars((string) ($certificate['quiz_topic'] ?? 'Tổng hợp'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="mt-1 text-sm text-slate-600">Mã chứng nhận: <strong><?= htmlspecialchars((string) ($certificate['certificate_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            <p class="mt-1 text-sm text-slate-600">Điểm đạt: <?= (float) ($certificate['score_percent'] ?? 0); ?>% | Uy tín nhận: +<?= (int) ($certificate['awarded_reputation_points'] ?? 0); ?></p>
                            <p class="mt-1 text-xs text-slate-400">Thời gian cấp: <?= htmlspecialchars((string) ($certificate['awarded_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-500">Chưa có chứng nhận nào.</div>
            <?php endif; ?>
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
            this.textContent = 'Đã sao chép liên kết';
            setTimeout(() => { this.textContent = 'Sao chép liên kết hồ sơ'; }, 1200);
        } catch (_) {}
    });
});
</script>

