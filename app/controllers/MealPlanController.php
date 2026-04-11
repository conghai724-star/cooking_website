<?php

declare(strict_types=1);

class MealPlanController extends Controller
{
    public function index(): void
    {
        require_login();

        $ownerId = (int) current_user_id();
        $range = $this->resolvePlanRange();
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $difficulty = (string) ($_GET['difficulty'] ?? '');
        if (!in_array($difficulty, ['', 'easy', 'medium', 'hard'], true)) {
            $difficulty = '';
        }
        $maxTime = (int) ($_GET['max_time'] ?? 0);
        if (!in_array($maxTime, [0, 15, 30, 45, 60], true)) {
            $maxTime = 0;
        }
        $selectedSlotDate = (string) ($_GET['slot_date'] ?? '');
        $selectedSlotMeal = (string) ($_GET['slot_meal'] ?? '');
        if (!$this->isValidDate($selectedSlotDate)) {
            $selectedSlotDate = '';
        }
        if (!in_array($selectedSlotMeal, ['breakfast', 'lunch', 'dinner'], true)) {
            $selectedSlotMeal = '';
        }
        $bankPage = max(1, (int) ($_GET['bank_page'] ?? 1));
        $bankPerPage = 8;
        $bankOffset = ($bankPage - 1) * $bankPerPage;

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $recipeBankTotal = $recipeModel->countPlannerBank($keyword !== '' ? $keyword : null, $difficulty !== '' ? $difficulty : null, $maxTime > 0 ? $maxTime : null);
        $recipeBank = $recipeModel->plannerBankPaged(
            $bankPerPage,
            $bankOffset,
            $keyword !== '' ? $keyword : null,
            $difficulty !== '' ? $difficulty : null,
            $maxTime > 0 ? $maxTime : null
        );
        $hasMoreBank = ($bankOffset + count($recipeBank)) < $recipeBankTotal;

        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        $settings = $mealPlanModel->getSettings($ownerId);
        $plans = $mealPlanModel->getWeeklyPlan($ownerId, $range['start'], $range['end'], false);
        $weekStartDate = $this->weekStartFromDate($range['start']);
        $weekLocked = $mealPlanModel->isWeekLocked($ownerId, $weekStartDate);
        $dayLocks = $mealPlanModel->getDayLocks($ownerId, $range['start'], $range['end']);

        $this->view('mealplans/index', [
            'title' => 'Lập kế hoạch bữa ăn',
            'useRecipeHubLayout' => true,
            'isOwner' => true,
            'planOwner' => current_user(),
            'settings' => $settings,
            'shareUrl' => URLROOT . '/meal-plans/shared/' . rawurlencode((string) ($settings['share_token'] ?? '')),
            'days' => $range['days'],
            'mode' => $range['mode'],
            'pivotDate' => $range['pivotDate'],
            'periodLabel' => $range['periodLabel'],
            'prevDate' => $range['prevDate'],
            'nextDate' => $range['nextDate'],
            'basePath' => '/meal-plans',
            'bankPage' => $bankPage,
            'bankHasMore' => $hasMoreBank,
            'bankFilters' => [
                'q' => $keyword,
                'difficulty' => $difficulty,
                'max_time' => $maxTime,
            ],
            'selectedSlot' => [
                'date' => $selectedSlotDate,
                'meal' => $selectedSlotMeal,
            ],
            'weekStartDate' => $weekStartDate,
            'weekLocked' => $weekLocked,
            'dayLocks' => $dayLocks,
            'plans' => $plans,
            'recipeBank' => $recipeBank,
            'notice' => (string) ($_GET['notice'] ?? ''),
        ]);
    }

    public function assign(): void
    {
        require_login();

        $userId = (int) current_user_id();
        $recipeId = (int) ($_POST['recipe_id'] ?? 0);
        $planDate = (string) ($_POST['plan_date'] ?? '');
        $mealType = (string) ($_POST['meal_type'] ?? '');
        $dishRole = (string) ($_POST['dish_role'] ?? 'main');
        if (!in_array($dishRole, ['main', 'side', 'soup', 'dessert', 'drink', 'other'], true)) {
            $dishRole = 'main';
        }

        if ($recipeId <= 0 || !$this->isValidDate($planDate) || !in_array($mealType, ['breakfast', 'lunch', 'dinner'], true)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Ô kế hoạch không hợp lệ.'], 422);
                return;
            }
            $this->redirect('/meal-plans?notice=invalid_slot');
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $recipe = $recipeModel->findById($recipeId);
        if (!$recipe) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Món ăn không tồn tại.'], 404);
                return;
            }
            $this->redirect('/meal-plans?notice=invalid_recipe');
        }

        if (isset($recipe['status']) && (string) $recipe['status'] !== 'approved') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Món ăn chưa được duyệt.'], 422);
                return;
            }
            $this->redirect('/meal-plans?notice=invalid_recipe');
        }
        if (isset($recipe['user_state']) && (string) $recipe['user_state'] === 'draft') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Món ăn đang ở trạng thái nháp.'], 422);
                return;
            }
            $this->redirect('/meal-plans?notice=invalid_recipe');
        }

        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        if ($this->isPlanDateLocked($mealPlanModel, $userId, $planDate)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Ngày này đã bị khóa, không thể chỉnh sửa.'], 422);
                return;
            }
            $this->redirect('/meal-plans?notice=slot_locked');
        }

        $mealPlanModel->assignMeal($userId, $planDate, $mealType, $recipeId, $dishRole);
        $plans = $mealPlanModel->getWeeklyPlan($userId, $planDate, $planDate, false);
        $assignedItemId = 0;
        foreach ($plans as $planItem) {
            if ((string) ($planItem['plan_date'] ?? '') !== $planDate) {
                continue;
            }
            if ((string) ($planItem['meal_type'] ?? '') !== $mealType) {
                continue;
            }
            if ((int) ($planItem['recipe_id'] ?? 0) !== $recipeId) {
                continue;
            }
            if ((string) ($planItem['dish_role'] ?? 'main') !== $dishRole) {
                continue;
            }
            $assignedItemId = (int) ($planItem['meal_plan_id'] ?? 0);
            if ($assignedItemId > 0) {
                break;
            }
        }

        if ($this->isAjaxRequest()) {
            $image = trim((string) ($recipe['image'] ?? ''));
            if ($image !== '' && !preg_match('/^https?:\\/\\//i', $image)) {
                $image = URLROOT . '/uploads/' . rawurlencode($image);
            }
            if ($image === '') {
                $image = 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80';
            }

            $this->jsonResponse([
                'ok' => true,
                'message' => 'Đã thêm món vào kế hoạch.',
                'slot' => [
                    'date' => $planDate,
                    'meal_type' => $mealType,
                ],
                'item_id' => $assignedItemId,
                'dish_role' => $dishRole,
                'recipe' => [
                    'id' => $recipeId,
                    'title' => (string) ($recipe['title'] ?? 'Cong thuc'),
                    'image' => $image,
                    'url' => URLROOT . '/recipes/' . $recipeId,
                ],
            ]);
            return;
        }

        $this->redirect($this->buildPlannerRedirect('assigned'));
    }

    public function remove(): void
    {
        require_login();

        $userId = (int) current_user_id();
        $planDate = (string) ($_POST['plan_date'] ?? '');
        $mealType = (string) ($_POST['meal_type'] ?? '');
        $mealPlanId = (int) ($_POST['plan_item_id'] ?? 0);

        if (!$this->isValidDate($planDate) || !in_array($mealType, ['breakfast', 'lunch', 'dinner'], true)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Ô kế hoạch không hợp lệ.'], 422);
                return;
            }
            $this->redirect('/meal-plans?notice=invalid_slot');
        }

        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        if ($this->isPlanDateLocked($mealPlanModel, $userId, $planDate)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Ngày này đã bị khóa, không thể chỉnh sửa.'], 422);
                return;
            }
            $this->redirect('/meal-plans?notice=slot_locked');
        }

        $mealPlanModel->removeMeal($userId, $planDate, $mealType, $mealPlanId > 0 ? $mealPlanId : null);

        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'ok' => true,
                'message' => 'Đã xóa món khỏi kế hoạch.',
                'slot' => [
                    'date' => $planDate,
                    'meal_type' => $mealType,
                ],
                'removed_item_id' => $mealPlanId > 0 ? $mealPlanId : null,
            ]);
            return;
        }

        $this->redirect($this->buildPlannerRedirect('removed'));
    }

    public function assignWeekAuto(): void
    {
        require_login();

        $mealType = (string) ($_POST['meal_type'] ?? '');
        if (!in_array($mealType, ['breakfast', 'lunch', 'dinner'], true)) {
            $this->redirect('/meal-plans?notice=invalid_slot');
        }

        $dates = $_POST['dates'] ?? [];
        if (!is_array($dates)) {
            $dates = [];
        }
        $validDates = [];
        foreach ($dates as $date) {
            $dateText = (string) $date;
            if ($this->isValidDate($dateText)) {
                $validDates[] = $dateText;
            }
        }
        $validDates = array_values(array_unique($validDates));
        if ($validDates === []) {
            $this->redirect('/meal-plans?notice=invalid_slot');
        }

        $recipeIds = $_POST['recipe_ids'] ?? [];
        if (!is_array($recipeIds)) {
            $recipeIds = [];
        }
        $recipeIds = array_values(array_unique(array_map('intval', $recipeIds)));
        $recipeIds = array_values(array_filter($recipeIds, static fn (int $id): bool => $id > 0));
        if ($recipeIds === []) {
            $this->redirect($this->buildPlannerRedirect('invalid_recipe'));
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $validRecipeIds = [];
        foreach ($recipeIds as $recipeId) {
            $recipe = $recipeModel->findById($recipeId);
            if (!$recipe) {
                continue;
            }
            if (isset($recipe['status']) && (string) $recipe['status'] !== 'approved') {
                continue;
            }
            if (isset($recipe['user_state']) && (string) $recipe['user_state'] === 'draft') {
                continue;
            }
            $validRecipeIds[] = $recipeId;
        }
        if ($validRecipeIds === []) {
            $this->redirect($this->buildPlannerRedirect('invalid_recipe'));
        }

        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        $userId = (int) current_user_id();
        $countRecipes = count($validRecipeIds);

        foreach ($validDates as $planDate) {
            if ($this->isPlanDateLocked($mealPlanModel, $userId, $planDate)) {
                $this->redirect($this->buildPlannerRedirect('slot_locked'));
            }
        }

        foreach ($validDates as $index => $planDate) {
            $recipeId = $validRecipeIds[$index % $countRecipes];
            $mealPlanModel->assignMeal($userId, $planDate, $mealType, $recipeId);
        }

        $this->redirect($this->buildPlannerRedirect('week_auto_assigned'));
    }

    public function updateVisibility(): void
    {
        require_login();

        $visibility = (string) ($_POST['visibility'] ?? '');
        if (!in_array($visibility, ['private', 'public', 'followers', 'friends', 'link'], true)) {
            $this->redirect('/meal-plans?notice=invalid_visibility');
        }

        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        $mealPlanModel->updateVisibility((int) current_user_id(), $visibility);

        $this->redirect('/meal-plans?notice=updated');
    }

    public function updateWeekLock(): void
    {
        require_login();

        $weekStartDate = (string) ($_POST['week_start_date'] ?? '');
        $isLocked = (string) ($_POST['is_locked'] ?? '0') === '1';
        if (!$this->isValidDate($weekStartDate)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Ô kế hoạch không hợp lệ.'], 422);
                return;
            }
            $this->redirect('/meal-plans?notice=invalid_slot');
        }

        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        $mealPlanModel->setWeekLock((int) current_user_id(), $weekStartDate, $isLocked);

        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'ok' => true,
                'is_locked' => $isLocked,
                'message' => $isLocked ? 'Đã khóa tuần kế hoạch này.' : 'Đã mở khóa tuần kế hoạch này.',
            ]);
            return;
        }

        $this->redirect($this->buildPlannerRedirect($isLocked ? 'week_locked' : 'week_unlocked'));
    }

    public function updateDayLock(): void
    {
        require_login();

        $lockDate = (string) ($_POST['lock_date'] ?? '');
        $isLocked = (string) ($_POST['is_locked'] ?? '0') === '1';
        if (!$this->isValidDate($lockDate)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Ô kế hoạch không hợp lệ.'], 422);
                return;
            }
            $this->redirect('/meal-plans?notice=invalid_slot');
        }

        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        $mealPlanModel->setDayLock((int) current_user_id(), $lockDate, $isLocked);

        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'ok' => true,
                'is_locked' => $isLocked,
                'message' => $isLocked ? 'Đã khóa ngày được chọn.' : 'Đã mở khóa ngày được chọn.',
            ]);
            return;
        }

        $this->redirect($this->buildPlannerRedirect($isLocked ? 'day_locked' : 'day_unlocked'));
    }

    public function regenerateLink(): void
    {
        require_login();

        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        $mealPlanModel->regenerateToken((int) current_user_id());

        $this->redirect('/meal-plans?notice=link_reset');
    }

    public function shared(string $token): void
    {
        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        $ownerId = $mealPlanModel->findUserIdByToken($token);
        if ($ownerId === null) {
            $this->notFound();
            return;
        }

        $settings = $mealPlanModel->getSettings($ownerId);
        $visibility = (string) ($settings['visibility'] ?? 'private');
        if ($visibility === 'private') {
            $this->notFound();
            return;
        }

        $this->renderPublicPlan($ownerId, true);
    }

    public function userPlans(string $id): void
    {
        $ownerId = (int) $id;
        if ($ownerId <= 0) {
            $this->notFound();
            return;
        }

        if (is_logged_in() && $ownerId === (int) current_user_id()) {
            $this->redirect('/meal-plans');
        }

        $this->renderPublicPlan($ownerId, false);
    }

    private function renderPublicPlan(int $ownerId, bool $fromSharedLink): void
    {
        /** @var MealPlanModel $mealPlanModel */
        $mealPlanModel = $this->model('MealPlanModel');
        /** @var UserModel $userModel */
        $userModel = $this->model('UserModel');
        /** @var FollowModel $followModel */
        $followModel = $this->model('FollowModel');

        $owner = $userModel->findById($ownerId);
        if (!$owner) {
            $this->notFound();
            return;
        }

        $settings = $mealPlanModel->getSettings($ownerId);
        $visibility = (string) ($settings['visibility'] ?? 'private');
        $viewerId = (int) (current_user_id() ?? 0);

        if ($viewerId > 0 && $viewerId !== $ownerId) {
            /** @var UserSafetyModel $safetyModel */
            $safetyModel = $this->model('UserSafetyModel');
            if ($safetyModel->isAnyBlockBetween($viewerId, $ownerId)) {
                $this->renderForbidden('mealplans/forbidden', [
                    'title' => 'Không thể xem kế hoạch',
                    'useRecipeHubLayout' => true,
                    'owner' => $owner,
                    'visibility' => $visibility,
                    'forbidden_reason' => 'block',
                ]);
                return;
            }
        }

        $canView = false;

        if ($visibility === 'public') {
            $canView = true;
        } elseif ($visibility === 'link') {
            $canView = $fromSharedLink;
        } elseif ($visibility === 'followers' && $viewerId > 0) {
            $canView = $followModel->isFollowing($viewerId, $ownerId);
        } elseif ($visibility === 'friends' && $viewerId > 0) {
            $canView = $followModel->isFollowing($viewerId, $ownerId) && $followModel->isFollowing($ownerId, $viewerId);
        }

        if (!$canView) {
            $this->renderForbidden('mealplans/forbidden', [
                'title' => 'Không thể xem kế hoạch',
                'useRecipeHubLayout' => true,
                'owner' => $owner,
                'visibility' => $visibility,
            ]);
            return;
        }

        $range = $this->resolvePlanRange();
        $plans = $mealPlanModel->getWeeklyPlan($ownerId, $range['start'], $range['end'], true);
        $weekStartDate = $this->weekStartFromDate($range['start']);
        $weekLocked = $mealPlanModel->isWeekLocked($ownerId, $weekStartDate);
        $dayLocks = $mealPlanModel->getDayLocks($ownerId, $range['start'], $range['end']);
        $plans = $this->applyLocksForViewer($plans, $weekLocked, $dayLocks);

        $basePath = $fromSharedLink
            ? '/meal-plans/shared/' . rawurlencode((string) ($settings['share_token'] ?? ''))
            : '/users/' . $ownerId . '/meal-plans';

        $this->view('mealplans/index', [
            'title' => 'Kế hoạch bữa ăn',
            'useRecipeHubLayout' => true,
            'isOwner' => false,
            'planOwner' => $owner,
            'settings' => $settings,
            'shareUrl' => '',
            'days' => $range['days'],
            'mode' => $range['mode'],
            'pivotDate' => $range['pivotDate'],
            'periodLabel' => $range['periodLabel'],
            'prevDate' => $range['prevDate'],
            'nextDate' => $range['nextDate'],
            'basePath' => $basePath,
            'selectedSlot' => [
                'date' => '',
                'meal' => '',
            ],
            'weekStartDate' => $weekStartDate,
            'weekLocked' => $weekLocked,
            'dayLocks' => $dayLocks,
            'plans' => $plans,
            'recipeBank' => [],
            'notice' => '',
        ]);
    }

    private function resolvePlanRange(): array
    {
        $mode = (string) ($_GET['mode'] ?? 'week');
        if (!in_array($mode, ['day', 'week'], true)) {
            $mode = 'week';
        }

        $inputDate = (string) ($_GET['date'] ?? '');
        $pivotDate = DateTimeImmutable::createFromFormat('Y-m-d', $inputDate) ?: new DateTimeImmutable('today');
        $pivotDate = $pivotDate->setTime(0, 0, 0);

        $days = [];
        if ($mode === 'day') {
            $dayOfWeek = (int) $pivotDate->format('N');
            $label = $dayOfWeek === 7 ? 'Chủ nhật' : ('Thứ ' . ($dayOfWeek + 1));
            $days[] = [
                'date' => $pivotDate->format('Y-m-d'),
                'label' => $label,
                'full_label' => $label,
            ];

            return [
                'mode' => 'day',
                'pivotDate' => $pivotDate->format('Y-m-d'),
                'start' => $pivotDate->format('Y-m-d'),
                'end' => $pivotDate->format('Y-m-d'),
                'days' => $days,
                'periodLabel' => $pivotDate->format('d/m/Y'),
                'prevDate' => $pivotDate->modify('-1 day')->format('Y-m-d'),
                'nextDate' => $pivotDate->modify('+1 day')->format('Y-m-d'),
            ];
        }

        $monday = $pivotDate->modify('monday this week');
        for ($i = 0; $i < 7; $i++) {
            $day = $monday->modify('+' . $i . ' day');
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $i === 6 ? 'CN' : ('T' . ($i + 2)),
                'full_label' => $i === 6 ? 'Chủ nhật' : ('Thứ ' . ($i + 2)),
            ];
        }

        return [
            'mode' => 'week',
            'pivotDate' => $pivotDate->format('Y-m-d'),
            'start' => $days[0]['date'],
            'end' => $days[6]['date'],
            'days' => $days,
            'periodLabel' => $monday->format('d/m/Y') . ' - ' . $monday->modify('+6 day')->format('d/m/Y'),
            'prevDate' => $pivotDate->modify('-7 day')->format('Y-m-d'),
            'nextDate' => $pivotDate->modify('+7 day')->format('Y-m-d'),
        ];
    }

    private function notFound(): void
    {
        $this->renderNotFound('Không tìm thấy kế hoạch bữa ăn.');
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }

    private function buildPlannerRedirect(string $notice): string
    {
        $mode = (string) ($_POST['return_mode'] ?? 'week');
        if (!in_array($mode, ['day', 'week'], true)) {
            $mode = 'week';
        }

        $date = (string) ($_POST['return_date'] ?? '');
        if (!$this->isValidDate($date)) {
            $date = (new DateTimeImmutable('today'))->format('Y-m-d');
        }

        $q = trim((string) ($_POST['return_q'] ?? ''));
        $difficulty = (string) ($_POST['return_difficulty'] ?? '');
        if (!in_array($difficulty, ['', 'easy', 'medium', 'hard'], true)) {
            $difficulty = '';
        }

        $maxTime = (int) ($_POST['return_max_time'] ?? 0);
        if (!in_array($maxTime, [0, 15, 30, 45, 60], true)) {
            $maxTime = 0;
        }

        $bankPage = max(1, (int) ($_POST['return_bank_page'] ?? 1));
        $slotDate = (string) ($_POST['return_slot_date'] ?? '');
        $slotMeal = (string) ($_POST['return_slot_meal'] ?? '');

        $parts = [
            'mode=' . rawurlencode($mode),
            'date=' . rawurlencode($date),
            'notice=' . rawurlencode($notice),
        ];

        if ($q !== '') {
            $parts[] = 'q=' . rawurlencode($q);
        }
        if ($difficulty !== '') {
            $parts[] = 'difficulty=' . rawurlencode($difficulty);
        }
        if ($maxTime > 0) {
            $parts[] = 'max_time=' . $maxTime;
        }
        if ($bankPage > 1) {
            $parts[] = 'bank_page=' . $bankPage;
        }
        if ($this->isValidDate($slotDate) && in_array($slotMeal, ['breakfast', 'lunch', 'dinner'], true)) {
            $parts[] = 'slot_date=' . rawurlencode($slotDate);
            $parts[] = 'slot_meal=' . rawurlencode($slotMeal);
        }

        return '/meal-plans?' . implode('&', $parts);
    }

    private function weekStartFromDate(string $date): string
    {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: new DateTimeImmutable('today');
        return $dt->modify('monday this week')->format('Y-m-d');
    }

    private function applyLocksForViewer(array $plans, bool $weekLocked, array $dayLocks): array
    {
        if ($weekLocked) {
            return [];
        }

        $visible = [];
        foreach ($plans as $plan) {
            $date = (string) ($plan['plan_date'] ?? '');
            if ($date !== '' && (($dayLocks[$date] ?? false) === true)) {
                continue;
            }
            $visible[] = $plan;
        }

        return $visible;
    }

    private function isAjaxRequest(): bool
    {
        $header = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return strtolower($header) === 'xmlhttprequest';
    }

    private function isPlanDateLocked(MealPlanModel $mealPlanModel, int $userId, string $planDate): bool
    {
        $weekStart = $this->weekStartFromDate($planDate);
        if ($mealPlanModel->isWeekLocked($userId, $weekStart)) {
            return true;
        }

        $dayLocks = $mealPlanModel->getDayLocks($userId, $planDate, $planDate);
        return ($dayLocks[$planDate] ?? false) === true;
    }
}
