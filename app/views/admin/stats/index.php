<?php
$from = (string) ($from ?? date('Y-m-d', strtotime('-29 days')));
$to = (string) ($to ?? date('Y-m-d'));
$granularity = (string) ($granularity ?? 'day');
if (!in_array($granularity, ['day', 'week', 'month'], true)) {
    $granularity = 'day';
}

$overview = is_array($overview ?? null) ? $overview : [];
$series = is_array($series ?? null) ? $series : [];
$topAuthors = is_array($topAuthors ?? null) ? $topAuthors : [];
$topContents = is_array($topContents ?? null) ? $topContents : [];

$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$labels = array_values(array_map(static fn($v): string => (string) $v, (array) ($series['labels'] ?? [])));
$registrations = array_values(array_map(static fn($v): int => (int) $v, (array) ($series['registrations'] ?? [])));
$submissions = array_values(array_map(static fn($v): int => (int) $v, (array) ($series['submissions'] ?? [])));
$reportsNew = array_values(array_map(static fn($v): int => (int) $v, (array) ($series['reports_new'] ?? [])));
$reportsResolved = array_values(array_map(static fn($v): int => (int) $v, (array) ($series['reports_resolved'] ?? [])));

$seriesCount = count($labels);
if ($seriesCount === 0) {
    $labels = ['No data'];
    $registrations = [0];
    $submissions = [0];
    $reportsNew = [0];
    $reportsResolved = [0];
}

$makePolyline = static function (array $values): string {
    $count = count($values);
    if ($count === 0) {
        return '';
    }
    $max = max($values);
    $max = $max > 0 ? $max : 1;
    $width = 100.0;
    $height = 36.0;
    $stepX = $count > 1 ? $width / ($count - 1) : 0.0;
    $points = [];
    foreach ($values as $i => $value) {
        $x = $count > 1 ? ($i * $stepX) : ($width / 2);
        $y = $height - (($value / $max) * $height);
        $points[] = round($x, 2) . ',' . round($y, 2);
    }
    return implode(' ', $points);
};

$totalForBars = static function (array $values): int {
    return array_sum(array_map(static fn($v): int => max(0, (int) $v), $values));
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-slate-900">Thống kê hệ thống</h1>
        <p class="text-sm text-slate-500">Tổng quan nhanh, xu hướng theo thời gian, và top tác giả/nội dung.</p>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <form method="get" action="<?= URLROOT; ?>/admin/stats" class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Từ ngày
                <input type="date" name="from" value="<?= $e($from); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Đến ngày
                <input type="date" name="to" value="<?= $e($to); ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
            </label>
            <label class="flex flex-col gap-1 text-xs font-semibold text-slate-600">
                Nhóm thời gian
                <select name="g" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
                    <option value="day" <?= $granularity === 'day' ? 'selected' : ''; ?>>Theo ngày</option>
                    <option value="week" <?= $granularity === 'week' ? 'selected' : ''; ?>>Theo tuần</option>
                    <option value="month" <?= $granularity === 'month' ? 'selected' : ''; ?>>Theo tháng</option>
                </select>
            </label>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Lọc</button>
            <a
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                href="<?= URLROOT; ?>/admin/stats/export?from=<?= urlencode($from); ?>&to=<?= urlencode($to); ?>&g=<?= urlencode($granularity); ?>"
            >
                Xuất CSV
            </a>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">User mới</p>
            <p class="mt-2 text-2xl font-bold text-slate-900"><?= (int) ($overview['users_new'] ?? 0); ?></p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">User active</p>
            <p class="mt-2 text-2xl font-bold text-slate-900"><?= (int) ($overview['users_active'] ?? 0); ?></p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Báo cáo mới / đã xử lý</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">
                <?= (int) ($overview['reports_new'] ?? 0); ?> / <?= (int) ($overview['reports_resolved'] ?? 0); ?>
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bình luận mới / ẩn-xóa</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">
                <?= (int) ($overview['comments_new'] ?? 0); ?> / <?= (int) ($overview['comments_hidden_deleted'] ?? 0); ?>
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tỷ lệ duyệt</p>
            <p class="mt-2 text-2xl font-bold text-slate-900"><?= $e((string) ($overview['approval_rate'] ?? 0)); ?>%</p>
            <p class="mt-1 text-xs text-slate-500">
                <?= (int) ($overview['total_approved'] ?? 0); ?> / <?= (int) ($overview['total_submitted'] ?? 0); ?> lượt nộp
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-semibold text-slate-800">Recipe</p>
            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                <div class="rounded bg-amber-50 px-3 py-2 text-amber-700">Chờ duyệt: <?= (int) (($overview['recipes']['pending'] ?? 0)); ?></div>
                <div class="rounded bg-emerald-50 px-3 py-2 text-emerald-700">Đã duyệt: <?= (int) (($overview['recipes']['approved'] ?? 0)); ?></div>
                <div class="rounded bg-rose-50 px-3 py-2 text-rose-700">Từ chối: <?= (int) (($overview['recipes']['rejected'] ?? 0)); ?></div>
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-semibold text-slate-800">Ingredient</p>
            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                <div class="rounded bg-amber-50 px-3 py-2 text-amber-700">Chờ duyệt: <?= (int) (($overview['ingredients']['pending'] ?? 0)); ?></div>
                <div class="rounded bg-emerald-50 px-3 py-2 text-emerald-700">Đã duyệt: <?= (int) (($overview['ingredients']['approved'] ?? 0)); ?></div>
                <div class="rounded bg-rose-50 px-3 py-2 text-rose-700">Từ chối: <?= (int) (($overview['ingredients']['rejected'] ?? 0)); ?></div>
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-semibold text-slate-800">Tip</p>
            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                <div class="rounded bg-amber-50 px-3 py-2 text-amber-700">Chờ duyệt: <?= (int) (($overview['tips']['pending'] ?? 0)); ?></div>
                <div class="rounded bg-emerald-50 px-3 py-2 text-emerald-700">Đã duyệt: <?= (int) (($overview['tips']['approved'] ?? 0)); ?></div>
                <div class="rounded bg-rose-50 px-3 py-2 text-rose-700">Từ chối: <?= (int) (($overview['tips']['rejected'] ?? 0)); ?></div>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-900">Xu hướng theo thời gian</h2>
            <span class="text-xs text-slate-500"><?= $e($from); ?> -> <?= $e($to); ?></span>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <?php
            $chartItems = [
                ['label' => 'Đăng ký mới', 'values' => $registrations, 'stroke' => '#2563eb'],
                ['label' => 'Nội dung submit', 'values' => $submissions, 'stroke' => '#9333ea'],
                ['label' => 'Báo cáo mới', 'values' => $reportsNew, 'stroke' => '#ea580c'],
                ['label' => 'Báo cáo đã xử lý', 'values' => $reportsResolved, 'stroke' => '#059669'],
            ];
            ?>
            <?php foreach ($chartItems as $item): ?>
                <?php $points = $makePolyline($item['values']); ?>
                <div class="rounded-lg border border-slate-100 p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-800"><?= $e($item['label']); ?></p>
                        <p class="text-xs text-slate-500">Tổng: <?= $totalForBars($item['values']); ?></p>
                    </div>
                    <svg viewBox="0 0 100 36" class="h-24 w-full rounded bg-slate-50">
                        <polyline fill="none" stroke="<?= $e($item['stroke']); ?>" stroke-width="1.8" points="<?= $e($points); ?>"></polyline>
                    </svg>
                    <p class="mt-2 line-clamp-1 text-[11px] text-slate-500">
                        Mốc đầu: <?= $e((string) ($labels[0] ?? '-')); ?> | Mốc cuối: <?= $e((string) ($labels[count($labels) - 1] ?? '-')); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="text-base font-semibold text-slate-900">Top tác giả (theo số bài)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-2 font-semibold">#</th>
                            <th class="px-4 py-2 font-semibold">Tác giả</th>
                            <th class="px-4 py-2 font-semibold">Tổng bài</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($topAuthors === []): ?>
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500">Không có dữ liệu.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topAuthors as $idx => $row): ?>
                                <tr>
                                    <td class="px-4 py-2"><?= $idx + 1; ?></td>
                                    <td class="px-4 py-2"><?= $e($row['author_name'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2 font-semibold text-slate-900"><?= (int) ($row['total_posts'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
            <div class="border-b border-slate-100 px-4 py-3">
                <h2 class="text-base font-semibold text-slate-900">Top nội dung tương tác</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-2 font-semibold">#</th>
                            <th class="px-4 py-2 font-semibold">Công thức</th>
                            <th class="px-4 py-2 font-semibold">Save</th>
                            <th class="px-4 py-2 font-semibold">Comment</th>
                            <th class="px-4 py-2 font-semibold">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($topContents === []): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Không có dữ liệu.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topContents as $idx => $row): ?>
                                <tr>
                                    <td class="px-4 py-2"><?= $idx + 1; ?></td>
                                    <td class="px-4 py-2 max-w-[320px] truncate"><?= $e($row['title'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2"><?= (int) ($row['save_count'] ?? 0); ?></td>
                                    <td class="px-4 py-2"><?= (int) ($row['comment_count'] ?? 0); ?></td>
                                    <td class="px-4 py-2 font-semibold text-slate-900"><?= (int) ($row['interaction_score'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

