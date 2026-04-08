
<?php
$isOwner = (bool) ($isOwner ?? false);
$planOwner = is_array($planOwner ?? null) ? $planOwner : [];
$settings = is_array($settings ?? null) ? $settings : [];
$recipeBank = is_array($recipeBank ?? null) ? $recipeBank : [];
$days = is_array($days ?? null) ? $days : [];
$plans = is_array($plans ?? null) ? $plans : [];
$notice = (string) ($notice ?? '');
$shareUrl = (string) ($shareUrl ?? '');
$mode = ($mode ?? 'week') === 'day' ? 'day' : 'week';
$pivotDate = (string) ($pivotDate ?? date('Y-m-d'));
$periodLabel = (string) ($periodLabel ?? '');
$prevDate = (string) ($prevDate ?? $pivotDate);
$nextDate = (string) ($nextDate ?? $pivotDate);
$basePath = (string) ($basePath ?? '/meal-plans');
$bankPage = max(1, (int) ($bankPage ?? 1));
$bankHasMore = (bool) ($bankHasMore ?? false);
$bankFilters = is_array($bankFilters ?? null) ? $bankFilters : [];
$bankKeyword = trim((string) ($bankFilters['q'] ?? ''));
$bankDifficulty = (string) ($bankFilters['difficulty'] ?? '');
$bankMaxTime = (int) ($bankFilters['max_time'] ?? 0);
$selectedSlot = is_array($selectedSlot ?? null) ? $selectedSlot : [];
$selectedSlotDate = (string) ($selectedSlot['date'] ?? '');
$selectedSlotMeal = (string) ($selectedSlot['meal'] ?? '');
$weekStartDate = (string) ($weekStartDate ?? '');
$weekLocked = (bool) ($weekLocked ?? false);
$dayLocks = is_array($dayLocks ?? null) ? $dayLocks : [];

$ownerName = trim((string) ($planOwner['name'] ?? $planOwner['username'] ?? 'Người dùng'));
if ($ownerName === '') {
    $ownerName = 'Người dùng';
}


$mealRows = [
    'breakfast' => 'Bữa sáng',
    'lunch' => 'Bữa trưa',
    'dinner' => 'Bữa tối',
];

$dishRoleLabels = [
    'main' => 'Món chính',
    'side' => 'Món phụ',
    'soup' => 'Canh',
    'dessert' => 'Tráng miệng',
    'drink' => 'Đồ uống',
    'other' => 'Khác',
];
$visibility = (string) ($settings['visibility'] ?? 'private');
$visibilityOptions = [
    'private' => 'Riêng tư',
    'public' => 'Công khai',
    'link' => 'Qua link',
    'followers' => 'Người theo dõi',
    'friends' => 'Bạn bè',
];

$planMap = [];
foreach ($plans as $item) {
    $d = (string) ($item['plan_date'] ?? '');
    $m = (string) ($item['meal_type'] ?? '');
    if ($d === '' || $m === '') {
        continue;
    }
    $planMap[$m][$d][] = $item;
}

$buildModeUrl = static function (string $targetMode, string $targetDate) use ($basePath): string {
    return URLROOT . $basePath . '?mode=' . rawurlencode($targetMode) . '&date=' . rawurlencode($targetDate);
};
$dayColumnsClass = $mode === 'day' ? 'grid-cols-1' : 'grid-cols-7';
$minWidth = $mode === 'day' ? 'min-w-[320px]' : 'min-w-[860px]';
$slotReady = $selectedSlotDate !== '' && in_array($selectedSlotMeal, ['breakfast', 'lunch', 'dinner'], true);
$noticeText = match ($notice) {
    'updated' => 'Đã cập nhật quyền chia sẻ kế hoạch.',
    'link_reset' => 'Đã tạo link chia sẻ mới.',
    'assigned' => 'Đã thêm món vào kế hoạch.',
    'removed' => 'Đã xóa món khỏi kế hoạch.',
    'slot_locked' => 'Ngày này đã bị khóa, không thể chỉnh sửa.',
    'week_locked' => 'Đã khóa tuần kế hoạch này.',
    'week_unlocked' => 'Đã mở khóa tuần kế hoạch này.',
    'day_locked' => 'Đã khóa ngày được chọn.',
    'day_unlocked' => 'Đã mở khóa ngày được chọn.',
    default => '',
};
?>
<section id="meal-plan-root" class="w-full" data-is-owner="<?= $isOwner ? '1' : '0'; ?>" data-selected-slot-date="<?= htmlspecialchars($selectedSlotDate, ENT_QUOTES, 'UTF-8'); ?>" data-selected-slot-meal="<?= htmlspecialchars($selectedSlotMeal, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="mx-auto max-w-[1400px]">
    <div class="mb-5 flex items-end justify-between gap-3">
      <div>
        <h1 class="text-4xl font-black tracking-tight text-slate-900">Lập kế hoạch bữa ăn</h1>
        <p class="mt-2 text-sm text-slate-500"><?= $isOwner ? 'Mỗi bữa có thể thêm nhiều món.' : ('Kế hoạch bữa ăn của ' . htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8')); ?></p>
      </div>
    </div>

    <div class="mb-4 flex items-center justify-between gap-2">
      <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1">
        <a href="<?= $buildModeUrl('day', $pivotDate); ?>" class="rounded-lg px-3 py-1.5 text-sm font-semibold <?= $mode === 'day' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">Theo ngày</a>
        <a href="<?= $buildModeUrl('week', $pivotDate); ?>" class="rounded-lg px-3 py-1.5 text-sm font-semibold <?= $mode === 'week' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'; ?>">Theo tuần</a>
      </div>
      <div class="flex items-center gap-2">
        <a class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600" href="<?= $buildModeUrl($mode, $prevDate); ?>">Trước</a>
        <span class="text-sm font-semibold text-slate-700"><?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600" href="<?= $buildModeUrl($mode, $nextDate); ?>">Sau</a>
      </div>
    </div>

    <?php if ($noticeText !== ''): ?>
      <div id="mealplan-notice" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?= htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php else: ?>
      <div id="mealplan-notice" class="mb-4 hidden rounded-xl border px-4 py-3 text-sm"></div>
    <?php endif; ?>

    <?php if ($isOwner): ?>
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary/10 bg-white p-3">
        <p class="text-xs font-semibold text-slate-600">
          Tuần hiện tại:
          <span data-week-lock-status class="font-bold <?= $weekLocked ? 'text-rose-600' : 'text-emerald-600'; ?>">
            <?= $weekLocked ? 'Đang khóa' : 'Đang mở'; ?>
          </span>
        </p>
        <form method="post" action="<?= URLROOT; ?>/meal-plans/week-lock" class="inline js-week-lock-form">
            <?= csrf_field(); ?>
          <input type="hidden" name="week_start_date" value="<?= htmlspecialchars($weekStartDate, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="is_locked" value="<?= $weekLocked ? '0' : '1'; ?>">
          <input type="hidden" name="return_mode" value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="return_date" value="<?= htmlspecialchars($pivotDate, ENT_QUOTES, 'UTF-8'); ?>">
          <button class="js-week-lock-btn rounded-lg px-3 py-2 text-xs font-bold <?= $weekLocked ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-rose-200 bg-rose-50 text-rose-700'; ?>" type="submit">
            <?= $weekLocked ? 'Mở khóa tuần' : 'Khóa tuần'; ?>
          </button>
        </form>
      </div>

      <div id="selected-slot-indicator" class="mb-4 rounded-xl border border-primary/10 bg-amber-50/40 px-4 py-3 text-sm text-slate-700">
        <?= $slotReady ? ('Đang chọn ô: <strong>' . htmlspecialchars($selectedSlotDate, ENT_QUOTES, 'UTF-8') . '</strong> - <strong>' . htmlspecialchars($mealRows[$selectedSlotMeal] ?? $selectedSlotMeal, ENT_QUOTES, 'UTF-8') . '</strong>') : 'Bạn chưa chọn ô. Hãy bấm vào một ô trong lịch trước khi thêm món.'; ?>
      </div>

      <div class="mb-5 rounded-2xl border border-primary/10 bg-white p-4">
        <div class="mb-3 text-xs font-black uppercase tracking-wider text-slate-700">Chia sẻ kế hoạch</div>
        <div class="flex flex-wrap items-center gap-3">
          <form method="post" action="<?= URLROOT; ?>/meal-plans/visibility" class="flex items-center gap-2">
              <?= csrf_field(); ?>
            <select name="visibility" class="rounded-lg border-slate-300 text-sm">
              <?php foreach ($visibilityOptions as $key => $label): ?>
                <option value="<?= $key; ?>" <?= $visibility === $key ? 'selected' : ''; ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
            <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white" type="submit">Lưu quyền</button>
          </form>
          <?php if ($shareUrl !== ''): ?>
            <input readonly class="min-w-[260px] flex-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs" value="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8'); ?>">
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 <?= $isOwner ? 'xl:grid-cols-4' : 'xl:grid-cols-1'; ?>">
      <?php if ($isOwner): ?>
        <aside class="rounded-2xl border border-primary/10 bg-white p-4 xl:col-span-1">
          <h2 class="text-lg font-black text-slate-900">Ngân hàng công thức</h2>
          <p class="mb-3 text-xs uppercase tracking-wider text-slate-400">Thêm vào ô đã chọn</p>
          <?php foreach ($recipeBank as $recipe): ?>
            <?php
              $recipeId = (int) ($recipe['id'] ?? 0);
              $title = trim((string) ($recipe['title'] ?? 'Công thức'));
              $image = trim((string) ($recipe['image'] ?? ''));
              if ($image !== '' && !preg_match('/^https?:\/\//i', $image)) {
                $image = URLROOT . '/uploads/' . rawurlencode($image);
              }
              if ($image === '') {
                $image = 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80';
              }
            ?>
            <article class="mb-3 rounded-xl border border-primary/10 bg-white p-3">
              <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="Ảnh công thức" class="mb-2 h-24 w-full rounded-lg object-cover">
              <p class="mb-2 truncate text-sm font-bold text-slate-900"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></p>
              <form method="post" action="<?= URLROOT; ?>/meal-plans/assign" class="js-assign-form">
                  <?= csrf_field(); ?>
                <input type="hidden" name="recipe_id" value="<?= $recipeId; ?>">
                <input type="hidden" name="plan_date" class="js-plan-date" value="<?= htmlspecialchars($selectedSlotDate, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="meal_type" class="js-meal-type" value="<?= htmlspecialchars($selectedSlotMeal, ENT_QUOTES, 'UTF-8'); ?>">
                <select name="dish_role" class="mb-2 w-full rounded-lg border-slate-300 text-xs">
                  <?php foreach ($dishRoleLabels as $roleKey => $roleLabel): ?>
                    <option value="<?= htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="js-assign-btn w-full rounded-lg px-3 py-2 text-xs font-bold <?= $slotReady ? 'bg-slate-900 text-white' : 'cursor-not-allowed bg-slate-100 text-slate-400'; ?>" type="submit" <?= $slotReady ? '' : 'disabled'; ?>><?= $slotReady ? 'Thêm vào ô đã chọn' : 'Chọn ô trước'; ?></button>
              </form>
            </article>
          <?php endforeach; ?>
          <?php if ($bankHasMore): ?>
            <a href="<?= URLROOT; ?>/meal-plans?mode=<?= rawurlencode($mode); ?>&date=<?= rawurlencode($pivotDate); ?>&bank_page=<?= $bankPage + 1; ?>" class="inline-flex rounded-lg border border-primary/20 bg-primary/10 px-3 py-2 text-xs font-bold text-primary">Tải thêm</a>
          <?php endif; ?>
        </aside>
      <?php endif; ?>

      <div class="overflow-x-auto rounded-2xl border border-primary/10 bg-white p-5 <?= $isOwner ? 'xl:col-span-3' : 'xl:col-span-1'; ?>">
        <div class="<?= $minWidth; ?>">
          <div class="mb-3 grid <?= $dayColumnsClass; ?> gap-3">
            <?php foreach ($days as $day): ?>
              <?php
              $dayDate = (string) ($day['date'] ?? '');
              $dayIsLocked = (bool) ($dayLocks[$dayDate] ?? false);
              ?>
              <div class="rounded-lg border border-slate-100 bg-slate-50 px-2 py-2 text-center" data-day-cell="<?= htmlspecialchars($dayDate, ENT_QUOTES, 'UTF-8'); ?>">
                <p class="text-xs font-black uppercase tracking-wider text-slate-400"><?= htmlspecialchars((string) ($day['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="mt-1 text-sm font-bold text-slate-700"><?= htmlspecialchars($dayDate, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($isOwner): ?>
                  <form method="post" action="<?= URLROOT; ?>/meal-plans/day-lock" class="mt-2 inline-block js-day-lock-form" data-lock-date="<?= htmlspecialchars($dayDate, ENT_QUOTES, 'UTF-8'); ?>">
                      <?= csrf_field(); ?>
                    <input type="hidden" name="lock_date" value="<?= htmlspecialchars($dayDate, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="is_locked" value="<?= $dayIsLocked ? '0' : '1'; ?>">
                    <input type="hidden" name="return_mode" value="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="return_date" value="<?= htmlspecialchars($pivotDate, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="js-day-lock-btn rounded-md px-2 py-1 text-[11px] font-bold <?= $dayIsLocked ? 'border border-emerald-200 bg-emerald-50 text-emerald-700' : 'border border-rose-200 bg-rose-50 text-rose-700'; ?>" type="submit">
                      <?= $dayIsLocked ? 'Mở ngày' : 'Khóa ngày'; ?>
                    </button>
                  </form>
                <?php else: ?>
                  <?php if ($dayIsLocked): ?><p class="mt-2 text-[11px] font-bold text-rose-600">Đang khóa</p><?php endif; ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <?php foreach ($mealRows as $mealKey => $mealLabel): ?>
            <div class="mb-3 mt-5 flex items-center gap-3">
              <span class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-300"><?= htmlspecialchars($mealLabel, ENT_QUOTES, 'UTF-8'); ?></span>
              <div class="h-px flex-1 bg-slate-100"></div>
            </div>
            <div class="grid <?= $dayColumnsClass; ?> gap-3">
              <?php foreach ($days as $day): ?>
                <?php
                  $date = (string) ($day['date'] ?? '');
                  $dayIsLocked = (bool) ($dayLocks[$date] ?? false);
                  $entries = $planMap[$mealKey][$date] ?? [];
                  $isActiveSlot = $isOwner && $selectedSlotDate === $date && $selectedSlotMeal === $mealKey;
                ?>
                <div data-slot-container data-slot-date="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>" data-slot-meal="<?= htmlspecialchars($mealKey, ENT_QUOTES, 'UTF-8'); ?>" data-slot-locked="<?= ($weekLocked || $dayIsLocked) ? '1' : '0'; ?>" data-week-locked="<?= $weekLocked ? '1' : '0'; ?>" data-day-locked="<?= $dayIsLocked ? '1' : '0'; ?>" class="min-h-[150px] rounded-xl border p-3 <?= $isActiveSlot ? 'border-primary bg-amber-50/30' : 'border-slate-100 bg-slate-50'; ?>">
                  <?php if ($weekLocked || $dayIsLocked): ?>
                    <div class="flex h-full min-h-[110px] flex-col items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-400">
                      <span class="material-symbols-outlined mb-1 text-lg">lock</span>
                      <p class="text-[11px] font-semibold"><?= $weekLocked ? 'Tuần này đang khóa' : 'Ngày này đang khóa'; ?></p>
                    </div>
                  <?php elseif (!empty($entries)): ?>
                    <div class="js-slot-list space-y-2">
                      <?php foreach ($entries as $entry): ?>
                        <?php
                          $entryImage = trim((string) ($entry['image'] ?? ''));
                          if ($entryImage !== '' && !preg_match('/^https?:\/\//i', $entryImage)) {
                            $entryImage = URLROOT . '/uploads/' . rawurlencode($entryImage);
                          }
                          if ($entryImage === '') {
                            $entryImage = 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80';
                          }
                          $entryTitle = trim((string) ($entry['title'] ?? 'CĂ´ng thA�»©c'));
                          $entryRecipeId = (int) ($entry['recipe_id'] ?? 0);
                          $entryPlanId = (int) ($entry['meal_plan_id'] ?? 0);
                          $entryDishRole = (string) ($entry['dish_role'] ?? 'main');
                          $entryDishRoleLabel = $dishRoleLabels[$entryDishRole] ?? 'KhÄ‚Â¡c';
                        ?>
                        <article class="js-slot-item rounded-lg border border-slate-200 bg-white p-2" data-plan-item-id="<?= $entryPlanId; ?>">
                          <img class="mb-2 h-20 w-full rounded-lg object-cover" src="<?= htmlspecialchars($entryImage, ENT_QUOTES, 'UTF-8'); ?>" alt="A�º¢nh cĂ´ng thA�»©c">
                          <div class="mb-1 flex items-center justify-between gap-2">
                            <p class="truncate text-xs font-bold text-slate-900"><?= htmlspecialchars($entryTitle, ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700"><?= htmlspecialchars($entryDishRoleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                          </div>
                          <div class="mt-2 flex items-center gap-2">
                            <?php if ($entryRecipeId > 0): ?><a class="text-[11px] font-semibold text-primary hover:underline" href="<?= URLROOT; ?>/recipes/<?= $entryRecipeId; ?>">Xem</a><?php endif; ?>
                            <?php if ($isOwner): ?>
                              <a class="js-slot-select text-[11px] font-semibold text-slate-500 hover:underline" href="javascript:void(0)" data-slot-date="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>" data-slot-meal="<?= htmlspecialchars($mealKey, ENT_QUOTES, 'UTF-8'); ?>">ChA�»n Ă´</a>
                              <form method="post" action="<?= URLROOT; ?>/meal-plans/remove" class="inline js-remove-form">
                                  <?= csrf_field(); ?>
                                <input type="hidden" name="plan_item_id" value="<?= $entryPlanId; ?>">
                                <input type="hidden" name="plan_date" value="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="meal_type" value="<?= htmlspecialchars($mealKey, ENT_QUOTES, 'UTF-8'); ?>">
                                <button class="text-[11px] font-semibold text-rose-600 hover:underline" type="submit">Xóa</button>
                              </form>
                            <?php endif; ?>
                          </div>
                        </article>
                      <?php endforeach; ?>
                    </div>
                  <?php elseif ($isOwner): ?>
                    <a class="js-slot-select js-slot-empty flex h-full items-center justify-center rounded-lg border-2 border-dashed border-primary/15 text-slate-400 hover:border-primary/40 hover:text-primary" href="javascript:void(0)" data-slot-date="<?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>" data-slot-meal="<?= htmlspecialchars($mealKey, ENT_QUOTES, 'UTF-8'); ?>">
                      <span class="material-symbols-outlined">add</span>
                    </a>
                  <?php else: ?>
                    <div class="flex h-full items-center justify-center rounded-lg border-2 border-dashed border-primary/15 text-slate-300"><span class="material-symbols-outlined">add</span></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($isOwner): ?>
<script>
(() => {
  const root = document.getElementById('meal-plan-root');
  if (!root) return;

  const state = {
    date: root.dataset.selectedSlotDate || '',
    meal: root.dataset.selectedSlotMeal || '',
  };
  const labels = { breakfast: 'BA�»¯a sĂ¡ng', lunch: 'BA�»¯a trA�°a', dinner: 'BA�»¯a tA�»‘i' };
  const notice = document.getElementById('mealplan-notice');
  const indicator = document.getElementById('selected-slot-indicator');
  const isSelectedSlotLocked = () => {
    if (!(state.date && state.meal)) return false;
    const slot = document.querySelector(`[data-slot-container][data-slot-date="${state.date}"][data-slot-meal="${state.meal}"]`);
    return slot ? slot.dataset.slotLocked === '1' : false;
  };
  const applyLockBtnStyle = (btn, isLocked) => {
    if (!btn) return;
    btn.classList.remove('border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'border-rose-200', 'bg-rose-50', 'text-rose-700');
    if (isLocked) {
      btn.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
    } else {
      btn.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-700');
    }
  };

  const setWeekLockState = (isLocked) => {
    document.querySelectorAll('[data-slot-container]').forEach((slot) => {
      slot.dataset.weekLocked = isLocked ? '1' : '0';
      const dayLocked = slot.dataset.dayLocked === '1';
      slot.dataset.slotLocked = (isLocked || dayLocked) ? '1' : '0';
    });
  };

  const setDayLockState = (date, isLocked) => {
    document.querySelectorAll(`[data-slot-container][data-slot-date="${date}"]`).forEach((slot) => {
      slot.dataset.dayLocked = isLocked ? '1' : '0';
      const weekLocked = slot.dataset.weekLocked === '1';
      slot.dataset.slotLocked = (weekLocked || isLocked) ? '1' : '0';
    });
  };
  const showNotice = (msg, error = false) => {
    if (!notice) return;
    notice.classList.remove('hidden');
    notice.textContent = msg;
    notice.className = `mb-4 rounded-xl border px-4 py-3 text-sm ${error ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`;
  };

  const refreshIndicator = () => {
    if (!indicator) return;
    indicator.innerHTML = (state.date && state.meal)
      ? `A�ang chA�»n Ă´: <strong>${state.date}</strong> - <strong>${labels[state.meal] || state.meal}</strong>`
      : 'BA�º¡n chA�°a chA�»n Ă´. HĂ£y bA�º¥m vĂ o mA�»™t Ă´ trong lA�»‹ch trA�°A�»›c khi thĂªm mĂ³n.';
  };

  const refreshAssignForms = () => {
    const locked = isSelectedSlotLocked();
    const ready = !!(state.date && state.meal) && !locked;
    document.querySelectorAll('.js-assign-form').forEach((form) => {
      const d = form.querySelector('.js-plan-date');
      const m = form.querySelector('.js-meal-type');
      const b = form.querySelector('.js-assign-btn');
      if (d) d.value = state.date;
      if (m) m.value = state.meal;
      if (!b) return;
      b.disabled = !ready;
      b.textContent = ready ? 'ThĂªm vĂ o Ă´ A�‘Ă£ chA�»n' : (locked ? 'Ă” A�‘ang khóa' : 'ChA�»n Ă´ trA�°A�»›c');
      b.classList.toggle('cursor-not-allowed', !ready);
      b.classList.toggle('bg-slate-100', !ready);
      b.classList.toggle('text-slate-400', !ready);
      b.classList.toggle('bg-slate-900', ready);
      b.classList.toggle('text-white', ready);
    });
  };

  const postForm = async (form) => {
    const res = await fetch(form.action, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body: new FormData(form)
    });
    const data = await res.json();
    if (!res.ok || !data.ok) throw new Error(data.message || 'CĂ³ lA�»—i xA�º£y ra.');
    return data;
  };

  document.addEventListener('click', (e) => {
    const select = e.target.closest('.js-slot-select');
    if (!select) return;
    e.preventDefault();
    state.date = select.dataset.slotDate || '';
    state.meal = select.dataset.slotMeal || '';
    if (isSelectedSlotLocked()) {
      showNotice('NgĂ y nĂ y A�‘Ă£ bA�»‹ khóa, khĂ´ng thA�»ƒ chA�»‰nh sA�»­a.', true);
    }
    refreshIndicator();
    refreshAssignForms();
  });

  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.classList.contains('js-week-lock-form')) {
      e.preventDefault();
      try {
        const data = await postForm(form);
        const isLocked = !!data.is_locked;
        const hidden = form.querySelector('input[name="is_locked"]');
        const btn = form.querySelector('.js-week-lock-btn');
        const status = document.querySelector('[data-week-lock-status]');
        if (hidden) hidden.value = isLocked ? '0' : '1';
        if (btn) {
          btn.textContent = isLocked ? 'MA�»Ÿ khóa tuA�º§n' : 'KhĂ³a tuA�º§n';
          applyLockBtnStyle(btn, isLocked);
        }
        if (status) {
          status.textContent = isLocked ? 'Đang khA�a' : 'Đang mở';
          status.classList.toggle('text-rose-600', isLocked);
          status.classList.toggle('text-emerald-600', !isLocked);
        }
        setWeekLockState(isLocked);
        refreshAssignForms();
        showNotice(data.message || 'A�Ă£ cA�º­p nhA�º­t khóa tuA�º§n.');
      } catch (err) {
        showNotice(err.message || 'Không thA�»ƒ cA�º­p nhA�º­t khóa tuA�º§n.', true);
      }
      return;
    }

    if (form.classList.contains('js-day-lock-form')) {
      e.preventDefault();
      const lockDate = form.dataset.lockDate || '';
      try {
        const data = await postForm(form);
        const isLocked = !!data.is_locked;
        const hidden = form.querySelector('input[name="is_locked"]');
        const btn = form.querySelector('.js-day-lock-btn');
        if (hidden) hidden.value = isLocked ? '0' : '1';
        if (btn) {
          btn.textContent = isLocked ? 'MA�»Ÿ ngày' : 'KhĂ³a ngày';
          applyLockBtnStyle(btn, isLocked);
        }
        if (lockDate !== '') {
          setDayLockState(lockDate, isLocked);
        }
        refreshAssignForms();
        showNotice(data.message || 'A�Ă£ cA�º­p nhA�º­t khóa ngày.');
      } catch (err) {
        showNotice(err.message || 'Không thA�»ƒ cA�º­p nhA�º­t khóa ngày.', true);
      }
      return;
    }

    if (form.classList.contains('js-assign-form')) {
      e.preventDefault();
      if (!(state.date && state.meal)) {
        showNotice('BA�º¡n cA�º§n chA�»n Ă´ trA�°A�»›c khi thĂªm mĂ³n.', true);
        return;
      }
      if (isSelectedSlotLocked()) {
        showNotice('NgĂ y nĂ y A�‘Ă£ bA�»‹ khóa, khĂ´ng thA�»ƒ chA�»‰nh sA�»­a.', true);
        return;
      }
      try {
        await postForm(form);
        window.location.reload();
      } catch (err) {
        showNotice(err.message || 'Không thA�»ƒ thĂªm mĂ³n.', true);
      }
    }

    if (form.classList.contains('js-remove-form')) {
      e.preventDefault();
      if (isSelectedSlotLocked()) {
        showNotice('NgĂ y nĂ y A�‘Ă£ bA�»‹ khóa, khĂ´ng thA�»ƒ chA�»‰nh sA�»­a.', true);
        return;
      }
      if (!confirm('XĂ³a mĂ³n khA�»i Ă´ nĂ y?')) return;
      try {
        await postForm(form);
        window.location.reload();
      } catch (err) {
        showNotice(err.message || 'Không thA�»ƒ xĂ³a mĂ³n.', true);
      }
    }
  });

  refreshIndicator();
  refreshAssignForms();
})();
</script>
<?php endif; ?>



























