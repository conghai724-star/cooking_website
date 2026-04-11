<?php

declare(strict_types=1);

class RecipeController extends Controller
{
    public function index(): void
    {
        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $difficulty = trim((string) ($_GET['difficulty'] ?? ''));
        $maxCookingTime = max(0, (int) ($_GET['max_time'] ?? 0));
        $healthy = isset($_GET['healthy']) && $_GET['healthy'] === '1';
        $perPage = 6;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = $recipeModel->countApproved($keyword !== '' ? $keyword : null, $difficulty !== '' ? $difficulty : null, $maxCookingTime > 0 ? $maxCookingTime : null, $healthy ? 'healthy' : null);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $recipes = $recipeModel->allApprovedPaged(
            $perPage,
            $offset,
            $keyword !== '' ? $keyword : null,
            $difficulty !== '' ? $difficulty : null,
            $maxCookingTime > 0 ? $maxCookingTime : null,
            $healthy ? 'healthy' : null
        );

        $this->view('recipes/index', [
            'title' => 'Công thức',
            'useRecipeHubLayout' => true,
            'recipes' => $recipes,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'difficulty' => $difficulty,
            'max_time' => $maxCookingTime,
            'healthy' => $healthy,
        ]);
    }

    public function show(string $id): void
    {
        $recipeId = (int) $id;
        if ($recipeId <= 0) {
            $this->renderNotFound('Không tìm thấy công thức.');
            return;
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $recipe = $recipeModel->findById($recipeId);
        if (!$recipe) {
            $this->renderNotFound('Không tìm thấy công thức.');
            return;
        }

        $viewerId = (int) (current_user_id() ?? 0);
        $isOwner = $viewerId > 0 && $viewerId === (int) ($recipe['user_id'] ?? 0);
        $isAdminUser = is_admin();

        if (!$this->isVisibleToViewer($recipe, $isOwner, $isAdminUser)) {
            $this->renderNotFound('Không tìm thấy công thức.');
            return;
        }

        $ingredients = $recipeModel->ingredientsByRecipe($recipeId);
        $steps = $recipeModel->stepsByRecipe($recipeId);

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $comments = $commentModel->byRecipe($recipeId);

        $isLoggedIn = is_logged_in();
        $isFollowing = false;
        $isSaved = false;
        if ($isLoggedIn) {
            $isSaved = $recipeModel->isSaved($viewerId, $recipeId);
            if (!$isOwner && (int) ($recipe['user_id'] ?? 0) > 0) {
                /** @var FollowModel $followModel */
                $followModel = $this->model('FollowModel');
                $isFollowing = $followModel->isFollowing($viewerId, (int) $recipe['user_id']);
            }
        }

        $this->view('recipes/show', [
            'title' => $recipe['title'] ?? 'Công thức',
            'useRecipeHubLayout' => true,
            'recipe' => $recipe,
            'ingredients' => $ingredients,
            'steps' => $steps,
            'comments' => $comments,
            'is_logged_in' => $isLoggedIn,
            'is_following' => $isFollowing,
            'is_saved' => $isSaved,
        ]);
    }

    public function export(string $id, string $format): void
    {
        $recipeId = (int) $id;
        if ($recipeId <= 0) {
            $this->renderNotFound('Không tìm thấy công thức.');
            return;
        }

        $format = strtolower(trim($format));
        if ($format === 'word') {
            $format = 'docx';
        }
        if (!in_array($format, ['pdf', 'docx', 'txt'], true)) {
            $this->renderNotFound('Định dạng xuất không hợp lệ.');
            return;
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $recipe = $recipeModel->findById($recipeId);
        if (!$recipe) {
            $this->renderNotFound('Không tìm thấy công thức.');
            return;
        }

        $viewerId = (int) (current_user_id() ?? 0);
        $isOwner = $viewerId > 0 && $viewerId === (int) ($recipe['user_id'] ?? 0);
        $isAdminUser = is_admin();
        if (!$this->isVisibleToViewer($recipe, $isOwner, $isAdminUser)) {
            $this->renderNotFound('Không tìm thấy công thức.');
            return;
        }

        $ingredients = $recipeModel->ingredientsByRecipe($recipeId);
        $steps = $recipeModel->stepsByRecipe($recipeId);
        $includeIngredients = $this->getExportFlag('include_ingredients', true);
        $includeCalories = $this->getExportFlag('include_calories', true);
        $includeImages = $this->getExportFlag('include_images', true);
        $calories = $includeCalories ? $recipeModel->estimateCaloriesByRecipe($recipeId) : null;
        $payload = $this->buildRecipeExportPayload($recipe, $ingredients, $steps, $calories, $includeIngredients, $includeImages, $includeCalories);
        $baseFilename = $this->buildRecipeExportFilename((string) ($recipe['title'] ?? 'recipe')) . '-' . $recipeId;

        if ($format === 'txt') {
            $this->downloadRecipeTxt($baseFilename, $payload);
            return;
        }
        if ($format === 'docx') {
            $this->downloadRecipeDocx($baseFilename, $payload);
            return;
        }

        $this->downloadRecipePdf($baseFilename, $payload);
    }

    public function create(): void
    {
        require_login();
        $this->abortIfRecipePostLocked('/recipes/my?group=all');

        $error = '';

        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel->byType('recipe');
        if ($categories === []) {
            $categories = $categoryModel->all();
        }
        /** @var TagModel $tagModel */
        $tagModel = $this->model('TagModel');
        $tagsByType = $tagModel->allGroupedByType();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = trim((string) ($_POST['action'] ?? 'submit'));
            if (!in_array($action, ['submit', 'save', 'cancel'], true)) {
                $action = 'submit';
            }

            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $title = trim(profanity_mask($title));
            $description = trim(profanity_mask($description));

            if ($action === 'submit' && ($title === '' || $description === '')) {
                $error = 'Vui lòng nhập tiêu đề và mô tả.';
            } else {
                if ($title === '') {
                    $title = 'Công thức nháp';
                }
                if ($description === '') {
                    $description = 'Đang cập nhật.';
                }

                $categoryId = trim((string) ($_POST['category_id'] ?? ''));
                $categoryValue = $categoryId !== '' ? (int) $categoryId : null;

                $difficulty = (string) ($_POST['difficulty'] ?? 'easy');
                if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
                    $difficulty = 'easy';
                }

                $cookingTime = trim((string) ($_POST['cooking_time'] ?? ''));
                $cookingTimeValue = $cookingTime !== '' ? (int) $cookingTime : null;

                $image = upload_image('image', APPROOT . '/public/uploads');
                $selectedTagIds = array_values(array_unique(array_filter(
                    array_map('intval', (array) ($_POST['tag_ids'] ?? [])),
                    static fn(int $v): bool => $v > 0
                )));

                $status = 'pending';
                $userState = 'completed';
                if ($action === 'save') {
                    $status = 'draft';
                    $userState = 'completed';
                } elseif ($action === 'cancel') {
                    $status = 'draft';
                    $userState = 'draft';
                }

                /** @var RecipeModel $recipeModel */
                $recipeModel = $this->model('RecipeModel');
                $recipeId = $recipeModel->create(
                    (int) current_user_id(),
                    $title,
                    $description,
                    $categoryValue,
                    $image,
                    $cookingTimeValue,
                    $difficulty,
                    $status,
                    $userState
                );

                if ($recipeId === false) {
                    $error = 'Không thể tạo công thức.';
                } else {
                    system_log_write('content_action', 'recipe.create', 'success', null, 'recipe', (int) $recipeId, [
                        'status' => $status,
                        'user_state' => $userState,
                    ], (int) current_user_id(), (string) (current_user()['role'] ?? 'user'));
                    if ($action === 'save') {
                        $recipeModel->setStatus($recipeId, 'draft');
                        $recipeModel->setUserState($recipeId, 'completed');
                    } elseif ($action === 'cancel') {
                        $recipeModel->setStatus($recipeId, 'draft');
                        $recipeModel->setUserState($recipeId, 'draft');
                    }
                    $this->syncIngredientsAndSteps($recipeId, $recipeModel, false);
                    $recipeModel->syncRecipeTags($recipeId, $selectedTagIds);

                    if ($action === 'save') {
                        $this->redirect('/recipes/my?group=completed');
                    }
                    if ($action === 'cancel') {
                        $this->redirect('/recipes/my?group=draft');
                    }

                    $this->redirect('/recipes/' . $recipeId . '?notice=created_success');
                }
            }
        }

        $this->view('recipes/create', [
            'title' => 'Đăng công thức',
            'useRecipeHubLayout' => true,
            'categories' => $categories,
            'tags_by_type' => $tagsByType,
            'selected_tag_ids' => [],
            'error' => $error,
        ]);
    }

    public function edit(string $id): void
    {
        require_login();
        $this->abortIfRecipePostLocked('/recipes/my?group=all');

        $recipeId = (int) $id;
        if ($recipeId <= 0) {
            $this->redirect('/recipes/my');
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $recipe = $recipeModel->findById($recipeId);
        if (!$recipe || (int) ($recipe['user_id'] ?? 0) !== (int) current_user_id()) {
            $this->redirect('/recipes/my');
        }
        if ($recipeModel->isApproved($recipeId)) {
            $this->redirect('/recipes/my?group=published&notice=approved_locked');
        }

        $error = '';

        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel->byType('recipe');
        if ($categories === []) {
            $categories = $categoryModel->all();
        }
        /** @var TagModel $tagModel */
        $tagModel = $this->model('TagModel');
        $tagsByType = $tagModel->allGroupedByType();
        $selectedTagIds = $recipeModel->tagIdsByRecipe($recipeId);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $title = trim(profanity_mask($title));
            $description = trim(profanity_mask($description));

            if ($title === '' || $description === '') {
                $error = 'Vui lòng nhập tiêu đề và mô tả.';
            } else {
                $categoryId = trim((string) ($_POST['category_id'] ?? ''));
                $categoryValue = $categoryId !== '' ? (int) $categoryId : null;

                $difficulty = (string) ($_POST['difficulty'] ?? 'easy');
                if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
                    $difficulty = 'easy';
                }

                $cookingTime = trim((string) ($_POST['cooking_time'] ?? ''));
                $cookingTimeValue = $cookingTime !== '' ? (int) $cookingTime : null;

                $image = upload_image('image', APPROOT . '/public/uploads');
                $imageValue = $image ?: (isset($recipe['image']) ? (string) $recipe['image'] : null);
                $selectedTagIds = array_values(array_unique(array_filter(
                    array_map('intval', (array) ($_POST['tag_ids'] ?? [])),
                    static fn(int $v): bool => $v > 0
                )));

                $recipeModel->updateDetailed(
                    $recipeId,
                    $title,
                    $description,
                    $categoryValue,
                    $cookingTimeValue,
                    $difficulty,
                    $imageValue
                );

                $recipeModel->clearRecipeIngredients($recipeId);
                $recipeModel->clearRecipeSteps($recipeId);
                $this->syncIngredientsAndSteps($recipeId, $recipeModel, true);
                $recipeModel->syncRecipeTags($recipeId, $selectedTagIds);
                system_log_write('content_action', 'recipe.edit', 'success', null, 'recipe', $recipeId, [
                    'title' => $title,
                    'difficulty' => $difficulty,
                ], (int) current_user_id(), (string) (current_user()['role'] ?? 'user'));

                $this->redirect('/recipes/' . $recipeId);
            }
        }

        $ingredients = $recipeModel->ingredientsByRecipe($recipeId);
        $steps = $recipeModel->stepsByRecipe($recipeId);

        $this->view('recipes/edit', [
            'title' => 'Chỉnh sửa công thức',
            'useRecipeHubLayout' => true,
            'recipe' => $recipe,
            'categories' => $categories,
            'tags_by_type' => $tagsByType,
            'selected_tag_ids' => $selectedTagIds,
            'ingredients' => $ingredients,
            'steps' => $steps,
            'error' => $error,
        ]);
    }

    public function myRecipes(): void
    {
        require_login();

        $userId = (int) current_user_id();

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $allOwn = $recipeModel->byUser($userId, null, null);
        $published = $recipeModel->byUser($userId, 'approved', 'published');
        $completed = $recipeModel->byUser($userId, null, 'completed');
        $draft = $recipeModel->byUser($userId, null, 'draft');
        $pending = $recipeModel->byUser($userId, 'pending', null);
        $rejected = $recipeModel->byUser($userId, 'rejected', null);

        $pending = array_values(array_filter($pending, static function (array $item): bool {
            $state = (string) ($item['user_state'] ?? '');
            return $state === '' || $state === 'completed' || $state === 'submitted';
        }));

        // Keep rejected items even if user_state is null (legacy rows)

        $completed = array_values(array_filter($completed, static function (array $item): bool {
            return (string) ($item['status'] ?? 'draft') === 'draft';
        }));

        $draft = array_values(array_filter($draft, static function (array $item): bool {
            return (string) ($item['status'] ?? 'draft') === 'draft';
        }));
        $saved = $recipeModel->savedByUser($userId);

        $ownedIds = [];
        foreach ($allOwn as $item) {
            $ownedIds[(int) ($item['id'] ?? 0)] = true;
        }

        $allItems = [];
        foreach ($allOwn as $item) {
            $item['item_type'] = 'own';
            $item['label'] = $this->labelForRecipe($item);
            $allItems[] = $item;
        }

        $savedItems = [];
        foreach ($saved as $item) {
            $item['item_type'] = 'saved';
            $item['label'] = 'Đã lưu';
            $savedItems[] = $item;

            $itemId = (int) ($item['id'] ?? 0);
            if (!isset($ownedIds[$itemId])) {
                $allItems[] = $item;
            }
        }

        $lists = [
            'all' => $allItems,
            'published' => $published,
            'completed' => $completed,
            'pending' => array_merge($pending, $rejected),
            'saved' => $savedItems,
            'draft' => $draft,
        ];

        $counts = [
            'all' => count($allItems),
            'published' => count($published),
            'completed' => count($completed),
            'pending' => count($pending) + count($rejected),
            'saved' => count($savedItems),
            'draft' => count($draft),
        ];

        $activeGroup = (string) ($_GET['group'] ?? 'all');

        $this->view('recipes/my_recipes', [
            'title' => 'Công thức của tôi',
            'useRecipeHubLayout' => true,
            'lists' => $lists,
            'counts' => $counts,
            'active_group' => $activeGroup,
        ]);
    }

    public function delete(string $id): void
    {
        require_login();

        $recipeId = (int) $id;
        if ($recipeId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            $recipe = $recipeModel->findById($recipeId);
            if (!$recipe || (int) ($recipe['user_id'] ?? 0) !== (int) current_user_id()) {
                $this->redirect('/recipes/my');
            }
            if ($recipeModel->isUsedInMealPlan($recipeId)) {
                $this->redirect('/recipes/my?group=all&notice=used_in_meal_plan');
            }
            $recipeModel->deleteOwned($recipeId, (int) current_user_id());
            system_log_write('content_action', 'recipe.delete', 'success', null, 'recipe', $recipeId, null, (int) current_user_id(), (string) (current_user()['role'] ?? 'user'));
        }

        $this->redirect('/recipes/my?group=draft');
    }

    public function moveToDraft(string $id): void
    {
        require_login();

        $recipeId = (int) $id;
        if ($recipeId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            $recipeModel->moveToDraft($recipeId, (int) current_user_id());
        }

        $this->redirect('/recipes/my?group=draft');
    }

    public function submit(string $id): void
    {
        require_login();
        $this->abortIfRecipePostLocked('/recipes/my?group=all');

        $recipeId = (int) $id;
        if ($recipeId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            $recipe = $recipeModel->findById($recipeId);
            if ($recipe && (int) ($recipe['user_id'] ?? 0) === (int) current_user_id()) {
                $recipeModel->setStatus($recipeId, 'pending');
                $recipeModel->setUserState($recipeId, 'completed');
            }
        }

        $this->redirect('/recipes/my?group=all');
    }

    public function resubmit(string $id): void
    {
        require_login();
        $this->abortIfRecipePostLocked('/recipes/my?group=pending');

        $recipeId = (int) $id;
        if ($recipeId > 0) {
            /** @var RecipeModel $recipeModel */
            $recipeModel = $this->model('RecipeModel');
            $recipe = $recipeModel->findById($recipeId);
            if ($recipe && (int) ($recipe['user_id'] ?? 0) === (int) current_user_id()) {
                $recipeModel->setStatus($recipeId, 'pending');
                $recipeModel->setUserState($recipeId, 'completed');
            }
        }

        $this->redirect('/recipes/my?group=pending');
    }

    public function save(): void
    {
        require_login();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonError('BAD_REQUEST', 'Phương thức không hợp lệ.', 400);
        }

        $recipeId = (int) ($_POST['recipe_id'] ?? 0);
        if ($recipeId <= 0) {
            $this->jsonError('BAD_REQUEST', 'Không tìm thấy công thức.', 400);
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $ok = $recipeModel->toggleSave((int) current_user_id(), $recipeId);
        if (!$ok) {
            $this->jsonError('SERVER_ERROR', 'Không thể cập nhật.', 500);
        }

        $saved = $recipeModel->isSaved((int) current_user_id(), $recipeId);
        $this->jsonResponse([
            'success' => true,
            'saved' => $saved,
            'message' => $saved ? 'Đã lưu công thức.' : 'Đã bỏ lưu công thức.',
        ], 200);
    }

    public function report(): void
    {
        require_login();

        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $isAjax = strcasecmp($requestedWith, 'XMLHttpRequest') === 0 || str_contains($accept, 'application/json');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            if ($isAjax) {
                $this->jsonError('BAD_REQUEST', 'Phương thức không hợp lệ.', 400);
            }
            $this->redirect('/recipes');
        }

        $recipeId = (int) ($_POST['recipe_id'] ?? 0);
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $reasonOther = trim((string) ($_POST['reason_other'] ?? ''));

        if ($reason === 'Khác' && $reasonOther !== '') {
            $reason = 'Khác: ' . $reasonOther;
        }

        if ($recipeId <= 0 || $reason === '') {
            if ($isAjax) {
                $this->jsonError('VALIDATION_ERROR', 'Vui lòng nhập lý do.', 422);
            }
            $this->redirect('/recipes/' . max(1, $recipeId));
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $ok = $recipeModel->saveReport((int) current_user_id(), $recipeId, $reason);

        if (!$ok) {
            if ($isAjax) {
                $this->jsonError('CONFLICT', 'Bạn đã báo cáo công thức này.', 409);
            }
            $this->redirect('/recipes/' . $recipeId . '?notice=recipe_reported_exists');
        }

        /** @var NotificationModel $notificationModel */
        $notificationModel = $this->model('NotificationModel');
        $notificationModel->createForAdmins(
            'report_recipe',
            'Có báo cáo công thức mới (ID: ' . $recipeId . ').'
        );

        if ($isAjax) {
            $this->jsonSuccess([], 'Đã gửi báo cáo.', 201);
        }

        $this->redirect('/recipes/' . $recipeId . '?notice=recipe_reported');
    }

    public function toggleFollow(): void
    {
        require_login();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonError('BAD_REQUEST', 'Phương thức không hợp lệ.', 400);
        }

        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $currentUserId = (int) current_user_id();

        if ($targetUserId <= 0 || $targetUserId === $currentUserId) {
            $this->jsonError('BAD_REQUEST', 'Không thể theo dõi.', 400);
        }

        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        if ($penaltyModel->getActiveFollowLock($currentUserId) !== null) {
            $this->jsonError('FOLLOW_LOCKED', 'Tài khoản đang bị khóa theo dõi tạm thời.', 403);
        }

        /** @var FollowModel $followModel */
        $followModel = $this->model('FollowModel');

        $isFollowing = $followModel->isFollowing($currentUserId, $targetUserId);
        if ($isFollowing) {
            $followModel->unfollow($currentUserId, $targetUserId);
        } else {
            $followModel->follow($currentUserId, $targetUserId);
        }

        $followingNow = $followModel->isFollowing($currentUserId, $targetUserId);
        if (!$isFollowing && $followingNow) {
            /** @var NotificationModel $notificationModel */
            $notificationModel = $this->model('NotificationModel');
            $actorName = (string) (current_user()['name'] ?? ('User #' . $currentUserId));
            $notificationModel->create(
                $targetUserId,
                'follow',
                $actorName . ' đã theo dõi bạn.',
                '/users/' . $currentUserId
            );
        }
        $this->jsonResponse([
            'success' => true,
            'following' => $followingNow,
            'message' => $followingNow ? 'Đã theo dõi.' : 'Đã hủy theo dõi.',
        ], 200);
    }

    private function syncIngredientsAndSteps(int $recipeId, RecipeModel $recipeModel, bool $useExistingImages): void
    {
        $names = $_POST['ingredient_name'] ?? [];
        $quantities = $_POST['ingredient_quantity'] ?? [];
        $units = $_POST['ingredient_unit'] ?? [];

        foreach ($names as $index => $nameRaw) {
            $name = trim((string) $nameRaw);
            if ($name === '') {
                continue;
            }

            $quantity = trim((string) ($quantities[$index] ?? ''));
            $unit = trim((string) ($units[$index] ?? ''));

            $quantityValue = $quantity !== '' ? $quantity : null;
            $unitValue = $unit !== '' ? $unit : null;

            $ingredient = $recipeModel->findIngredientByName($name);
            if (!$ingredient) {
                // Do not auto-create ingredient from recipe form.
                // Ingredients must be managed in the ingredient module.
                continue;
            }

            $ingredientId = (int) ($ingredient['id'] ?? 0);

            if ($ingredientId > 0) {
                $recipeModel->addRecipeIngredient($recipeId, $ingredientId, $quantityValue, $unitValue);
            }
        }

        $stepContents = $_POST['step_content'] ?? [];
        $existingImages = $_POST['step_existing_image'] ?? [];
        $stepNumber = 1;

        foreach ($stepContents as $index => $contentRaw) {
            $content = trim((string) $contentRaw);
            $content = trim(profanity_mask($content));
            $image = upload_image_from_array('step_images', (int) $index, APPROOT . '/public/uploads');

            if ($image === null && $useExistingImages) {
                $image = trim((string) ($existingImages[$index] ?? ''));
                if ($image === '') {
                    $image = null;
                }
            }

            if ($content === '' && $image === null) {
                continue;
            }

            $recipeModel->addRecipeStep($recipeId, $stepNumber, $content, $image);
            $stepNumber++;
        }
    }

    private function getExportFlag(string $key, bool $default): bool
    {
        if (!array_key_exists($key, $_GET)) {
            return $default;
        }
        $value = trim((string) ($_GET[$key] ?? ''));
        if ($value === '') {
            return true;
        }
        return !in_array(strtolower($value), ['0', 'false', 'off', 'no'], true);
    }

    private function buildRecipeExportPayload(
        array $recipe,
        array $ingredients,
        array $steps,
        ?float $calories,
        bool $includeIngredients,
        bool $includeImages,
        bool $includeCalories
    ): array {
        $filteredIngredients = [];
        if ($includeIngredients) {
            foreach ($ingredients as $item) {
                $name = trim((string) ($item['ingredient_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $quantity = trim((string) ($item['quantity'] ?? ''));
                $unit = trim((string) ($item['unit'] ?? ''));
                $prefix = trim($quantity . ' ' . $unit);
                $filteredIngredients[] = [
                    'name' => $name,
                    'display' => ($prefix !== '' ? ($prefix . ' ') : '') . $name,
                ];
            }
        }

        $filteredSteps = [];
        foreach ($steps as $index => $step) {
            $stepNo = (int) ($step['step_number'] ?? ($index + 1));
            if ($stepNo <= 0) {
                $stepNo = $index + 1;
            }
            $content = trim((string) ($step['content'] ?? ''));
            if ($content === '') {
                $content = 'Đang cập nhật mô tả bước này.';
            }
            $image = trim((string) ($step['image'] ?? ''));
            $filteredSteps[] = [
                'step_number' => $stepNo,
                'content' => $content,
                'image' => $includeImages ? $image : '',
            ];
        }

        $recipeImage = trim((string) ($recipe['image'] ?? ''));
        if (!$includeImages) {
            $recipeImage = '';
        }

        return [
            'title' => trim((string) ($recipe['title'] ?? 'Công thức')),
            'description' => trim((string) ($recipe['description'] ?? '')),
            'category' => trim((string) ($recipe['category_name'] ?? 'Chưa phân loại')),
            'author' => trim((string) ($recipe['author_name'] ?? 'Không rõ')),
            'difficulty' => trim((string) ($recipe['difficulty'] ?? 'easy')),
            'cooking_time' => (int) ($recipe['cooking_time'] ?? 0),
            'servings' => (int) ($recipe['servings'] ?? 0),
            'ingredients' => $filteredIngredients,
            'steps' => $filteredSteps,
            'recipe_image' => $recipeImage,
            'calories' => $includeCalories ? (float) ($calories ?? 0) : null,
            'include_images' => $includeImages,
            'include_ingredients' => $includeIngredients,
            'include_calories' => $includeCalories,
        ];
    }

    private function buildRecipeExportFilename(string $title): string
    {
        $base = trim($title);
        if ($base === '') {
            return 'cong-thuc';
        }

        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'D' => 'Đ',
            'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
            'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
        ];
        foreach ($unicode as $nonUnicode => $vn) {
            $base = preg_replace("/($vn)/u", $nonUnicode, $base) ?? $base;
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: $base;
        $ascii = strtolower($ascii);
        $ascii = preg_replace('/[^a-z0-9]+/i', '-', $ascii) ?? '';
        $ascii = trim($ascii, '-');

        return $ascii !== '' ? $ascii : 'cong-thuc';
    }

    private function buildRecipeExportText(array $payload): string
    {
        $difficultyLabels = [
            'easy' => 'Dễ',
            'medium' => 'Trung bình',
            'hard' => 'Khó',
        ];

        $title = trim((string) ($payload['title'] ?? 'Công thức'));
        if ($title === '') {
            $title = 'Công thức';
        }
        $description = trim((string) ($payload['description'] ?? ''));
        $difficulty = strtolower((string) ($payload['difficulty'] ?? 'easy'));
        $difficultyLabel = $difficultyLabels[$difficulty] ?? ucfirst($difficulty);
        $category = trim((string) ($payload['category'] ?? 'Chưa phân loại'));
        $author = trim((string) ($payload['author'] ?? 'Không rõ'));
        $cookingTime = (int) ($payload['cooking_time'] ?? 0);
        $servings = (int) ($payload['servings'] ?? 0);
        $ingredients = (array) ($payload['ingredients'] ?? []);
        $steps = (array) ($payload['steps'] ?? []);
        $includeIngredients = (bool) ($payload['include_ingredients'] ?? true);
        $includeCalories = (bool) ($payload['include_calories'] ?? true);
        $includeImages = (bool) ($payload['include_images'] ?? true);
        $calories = $payload['calories'] ?? null;

        $lines = [];
        $lines[] = $title;
        $lines[] = str_repeat('=', max(12, strlen($title)));
        $lines[] = '';
        $lines[] = 'Danh mục: ' . ($category !== '' ? $category : 'Chưa phân loại');
        $lines[] = 'Tác giả: ' . ($author !== '' ? $author : 'Không rõ');
        $lines[] = 'Độ khó: ' . ($difficultyLabel !== '' ? $difficultyLabel : 'Dễ');
        $lines[] = 'Thời gian nấu: ' . ($cookingTime > 0 ? ($cookingTime . ' phút') : 'Đang cập nhật');
        $lines[] = 'Khẩu phần: ' . ($servings > 0 ? ($servings . ' người') : 'Đang cập nhật');
        if ($includeCalories && $calories !== null) {
            $lines[] = 'Calories ước tính: ' . number_format((float) $calories, 0, '.', '') . ' kcal';
        }
        $lines[] = '';
        $lines[] = 'Mô tả';
        $lines[] = '-----';
        $lines[] = $description !== '' ? $description : 'Đang cập nhật mô tả.';
        $lines[] = '';
        if ($includeIngredients) {
            $lines[] = 'Nguyên liệu';
            $lines[] = '----------';
            if ($ingredients === []) {
                $lines[] = '- Đang cập nhật nguyên liệu.';
            } else {
                foreach ($ingredients as $item) {
                    $display = trim((string) ($item['display'] ?? ''));
                    if ($display === '') {
                        continue;
                    }
                    $lines[] = '- ' . $display;
                }
            }
            $lines[] = '';
        }
        $lines[] = 'Các bước thực hiện';
        $lines[] = '--------';
        if ($steps === []) {
            $lines[] = '1. Đang cập nhật các bước thực hiện.';
        } else {
            foreach ($steps as $index => $step) {
                $stepNo = (int) ($step['step_number'] ?? ($index + 1));
                if ($stepNo <= 0) {
                    $stepNo = $index + 1;
                }
                $content = trim((string) ($step['content'] ?? ''));
                if ($content === '') {
                    $content = 'Đang cập nhật mô tả bước này.';
                }
                $lines[] = $stepNo . '. ' . $content;
                // if ($includeImages) {
                //     $image = trim((string) ($step['image'] ?? ''));
                //     if ($image !== '') {
                //         $lines[] = '   [Hinh anh] ' . URLROOT . '/uploads/' . ltrim($image, '/');
                //     }
                // }
            }
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    private function imageSrcToDataUri(string $imageName): string
    {
        $path = rtrim(APPROOT, '/') . '/public/uploads/' . ltrim($imageName, '/');
        if (is_file($path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = 'image/jpeg';
            if ($ext === 'png') {
                $mime = 'image/png';
            } elseif ($ext === 'webp') {
                $mime = 'image/webp';
            } elseif ($ext === 'gif') {
                $mime = 'image/gif';
            } elseif ($ext === 'svg') {
                $mime = 'image/svg+xml';
            }
            $data = file_get_contents($path);
            if ($data !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        }
        return htmlspecialchars(URLROOT . '/uploads/' . ltrim($imageName, '/'), ENT_QUOTES, 'UTF-8');
    }

    private function buildRecipeExportHtml(array $payload): string
    {
        $title = htmlspecialchars((string) ($payload['title'] ?? 'Công thức'), ENT_QUOTES, 'UTF-8');
        $description = nl2br(htmlspecialchars((string) ($payload['description'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $category = htmlspecialchars((string) ($payload['category'] ?? 'Chưa phân loại'), ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars((string) ($payload['author'] ?? 'Không rõ'), ENT_QUOTES, 'UTF-8');

        $difficultyRaw = (string) ($payload['difficulty'] ?? 'easy');
        $diffMap = ['easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó'];
        $difficulty = htmlspecialchars($diffMap[$difficultyRaw] ?? 'Dễ', ENT_QUOTES, 'UTF-8');

        $cookingTime = (int) ($payload['cooking_time'] ?? 0);
        $timeStr = $cookingTime > 0 ? ($cookingTime . ' phút') : 'Đang cập nhật';

        $servings = (int) ($payload['servings'] ?? 0);
        $servingsStr = $servings > 0 ? ($servings . ' người') : 'Đang cập nhật';

        $includeIngredients = (bool) ($payload['include_ingredients'] ?? true);
        $includeCalories = (bool) ($payload['include_calories'] ?? true);
        $includeImages = (bool) ($payload['include_images'] ?? true);
        $calories = $payload['calories'] ?? null;
        $ingredients = (array) ($payload['ingredients'] ?? []);
        $steps = (array) ($payload['steps'] ?? []);
        $recipeImage = trim((string) ($payload['recipe_image'] ?? ''));

        $ingredientsHtml = '';
        if ($includeIngredients) {
            if ($ingredients === []) {
                $ingredientsHtml .= '<li>Đang cập nhật nguyên liệu.</li>';
            } else {
                foreach ($ingredients as $item) {
                    $display = trim((string) ($item['display'] ?? ''));
                    if ($display === '') {
                        continue;
                    }
                    $ingredientsHtml .= '<li>' . htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . '</li>';
                }
            }
        }

        $stepsHtml = '';
        if ($steps === []) {
            $stepsHtml .= '<li>Đang cập nhật các bước thực hiện.</li>';
        } else {
            foreach ($steps as $index => $step) {
                $stepNo = (int) ($step['step_number'] ?? ($index + 1));
                if ($stepNo <= 0) {
                    $stepNo = $index + 1;
                }
                $content = trim((string) ($step['content'] ?? ''));
                if ($content === '') {
                    $content = 'Đang cập nhật mô tả bước này.';
                }

                $imgHtml = '';
                if ($includeImages) {
                    $image = trim((string) ($step['image'] ?? ''));
                    if ($image !== '') {
                        $src = $this->imageSrcToDataUri($image);
                        $imgHtml = '<div class="step-img" style="text-align:center;"><img src="' . $src . '" alt="step-image" width="220"></div>';
                    }
                }

                $stepsHtml .= '<li><div class="step-content">' . nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . '</div>' . $imgHtml . '</li>';
            }
        }

        $recipeImageHtml = '';
        if ($includeImages && $recipeImage !== '') {
            $src = $this->imageSrcToDataUri($recipeImage);
            $recipeImageHtml = '<div class="recipe-image"><img src="' . $src . '" alt="recipe-image" width="340"></div>';
        }

        $caloriesHtml = '';
        if ($includeCalories && $calories !== null) {
            $caloriesHtml = '<span>Calories ước tính: ' . number_format((float) $calories, 0, '.', '') . ' kcal</span>';
        }

        $ingredientsSection = '';
        if ($includeIngredients) {
            $ingredientsSection = '<h2>Nguyên liệu chuẩn bị</h2><ul class="ingredients-list">' . $ingredientsHtml . '</ul>';
        }

        $descSection = '';
        if ($description !== '') {
            $descSection = '<div class="description">' . $description . '</div>';
        } else {
            $descSection = '<div class="description">Đang cập nhật mô tả.</div>';
        }

        $siteName = defined('SITENAME') ? SITENAME : 'Website Nấu Ăn';

        return '<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 0; background-color: #ffffff; }
        .container { padding: 30px; margin: 0 auto; max-width: 800px; }
        .header { text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 20px; }
        h1 { color: #f97316; font-size: 28px; margin: 0 0 15px 0; font-weight: bold; }
        .meta-tags { color: #64748b; font-size: 13px; line-height: 2.2; }
        .meta-tags span { display: inline-block; background: #f1f5f9; padding: 4px 12px; border-radius: 20px; margin: 0 5px 5px 0; border: 1px solid #e2e8f0; }

        .recipe-image { text-align: center; margin: 0 0 30px 0; }
        .recipe-image img { max-width: 100%; max-height: 350px; border-radius: 12px; }

        h2 { color: #0f172a; font-size: 20px; border-bottom: 2px solid #fdba74; padding-bottom: 8px; margin-top: 30px; margin-bottom: 15px; }
        .description { font-style: italic; color: #475569; background: #fff7ed; padding: 15px 20px; border-left: 4px solid #f97316; border-radius: 0 8px 8px 0; margin-bottom: 30px; }

        .ingredients-list { margin: 0; padding-left: 20px; list-style-type: none; }
        .ingredients-list li { margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #e2e8f0; position: relative; }
        .ingredients-list li::before { content: "•"; color: #f97316; font-weight: bold; display: inline-block; width: 1em; margin-left: -1em; }

        .steps-list { margin: 0; padding: 0; list-style-type: none; counter-reset: step-counter; }
        .steps-list li { margin-bottom: 25px; padding-left: 45px; position: relative; page-break-inside: avoid; }
        .steps-list li::before {
            counter-increment: step-counter; content: counter(step-counter);
            position: absolute; left: 0; top: 0;
            width: 30px; height: 30px; background: #f97316; color: white;
            border-radius: 15px; text-align: center; line-height: 30px; font-weight: bold; font-size: 15px;
        }
        .step-content { padding-top: 4px; }
        .step-img { margin-top: 12px; }
        .step-img img { max-width: 350px; border-radius: 8px; border: 1px solid #e2e8f0; }

        .footer { margin-top: 50px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 20px; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . $title . '</h1>
            <div class="meta-tags">
                <span>Tác giả: ' . $author . '</span>
                <span>Danh mục: ' . $category . '</span>
                <span>Độ khó: ' . $difficulty . '</span>
                <span>Thời gian nấu: ' . $timeStr . '</span>
                <span>Khẩu phần: ' . $servingsStr . '</span>
                ' . $caloriesHtml . '
            </div>
        </div>

        ' . $recipeImageHtml . '

        ' . $descSection . '

        ' . $ingredientsSection . '

        <h2>Các bước thực hiện</h2>
        <ol class="steps-list">
            ' . $stepsHtml . '
        </ol>

        <div class="footer">
            Xuất từ ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' - Chúc bạn nấu ăn ngon miệng!
        </div>
    </div>
</body>
</html>';
    }

    private function downloadRecipeTxt(string $baseFilename, array $payload): void
    {
        $filename = $baseFilename . '.txt';
        $text = $this->buildRecipeExportText($payload);

        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        echo "\xEF\xBB\xBF" . $text;
        exit;
    }

    private function downloadRecipeDocx(string $baseFilename, array $payload): string|null
    {
        $this->tryLoadVendorAutoload();

        if (class_exists('\\PhpOffice\\PhpWord\\PhpWord') && class_exists('\\PhpOffice\\PhpWord\\IOFactory')) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'recipe_docx_');
            if ($tmpFile !== false) {
                $docxPath = $tmpFile . '.docx';
                @unlink($docxPath);
                try {
                    $phpWord = new \PhpOffice\PhpWord\PhpWord();
                    $section = $phpWord->addSection();

                    // Add Recipe Image at the top
                    $includeImages = (bool) ($payload['include_images'] ?? true);
                    $recipeImage = trim((string) ($payload['recipe_image'] ?? ''));
                    if ($includeImages && $recipeImage !== '') {
                        $imgPath = rtrim(APPROOT, '/') . '/public/uploads/' . ltrim($recipeImage, '/');
                        if (is_file($imgPath)) {
                            // width array specifies points (approx 1 pt = 1.33 px)
                            $section->addImage($imgPath, ['width' => 150, 'height' => 150, 'ratio' => true, 'alignment' => 'center']);
                            $section->addTextBreak();
                        }
                    }

                    $section->addTitle((string) ($payload['title'] ?? 'Công thức'), 1);
                    $section->addText('Danh mục: ' . (string) ($payload['category'] ?? 'Chưa phân loại'));
                    $section->addText('Tác giả: ' . (string) ($payload['author'] ?? 'Không rõ'));
                    $section->addText('Độ khó: ' . (string) ($payload['difficulty'] ?? 'easy'));
                    $section->addText('Thời gian nấu: ' . ((int) ($payload['cooking_time'] ?? 0) > 0 ? ((int) $payload['cooking_time'] . ' phút') : 'Đang cập nhật'));
                    $section->addText('Khẩu phần: ' . ((int) ($payload['servings'] ?? 0) > 0 ? ((int) $payload['servings'] . ' người') : 'Đang cập nhật'));
                    if (($payload['include_calories'] ?? false) && array_key_exists('calories', $payload) && $payload['calories'] !== null) {
                        $section->addText('Calories ước tính: ' . number_format((float) $payload['calories'], 0, '.', '') . ' kcal');
                    }
                    $section->addTextBreak();
                    $section->addTitle('Mô tả', 2);
                    $section->addText((string) ($payload['description'] ?? 'Đang cập nhật mô tả.'));
                    if ($payload['include_ingredients'] ?? true) {
                        $section->addTextBreak();
                        $section->addTitle('Nguyên liệu', 2);
                        $ingredients = (array) ($payload['ingredients'] ?? []);
                        if ($ingredients === []) {
                            $section->addText('- Đang cập nhật nguyên liệu.');
                        } else {
                            foreach ($ingredients as $item) {
                                $section->addText('- ' . (string) ($item['display'] ?? ''));
                            }
                        }
                    }
                    $section->addTextBreak();
                    $section->addTitle('Các bước thực hiện', 2);
                    $steps = (array) ($payload['steps'] ?? []);
                    if ($steps === []) {
                        $section->addText('1. Đang cập nhật các bước thực hiện.');
                    } else {
                        foreach ($steps as $index => $step) {
                            $stepNo = (int) ($step['step_number'] ?? ($index + 1));
                            $section->addText($stepNo . '. ' . (string) ($step['content'] ?? ''));

                            if ($includeImages) {
                                $stepImg = trim((string) ($step['image'] ?? ''));
                                if ($stepImg !== '') {
                                    $stepImgPath = rtrim(APPROOT, '/') . '/public/uploads/' . ltrim($stepImg, '/');
                                    if (is_file($stepImgPath)) {
                                        $section->addImage($stepImgPath, ['width' => 120, 'ratio' => true, 'alignment' => 'center']);
                                        $section->addTextBreak();
                                    }
                                }
                            }
                        }
                    }

                    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                    $writer->save($docxPath);
                    if (is_file($docxPath)) {
                        $binary = file_get_contents($docxPath);
                        if (is_string($binary) && $binary !== '') {
                            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                            header('Content-Disposition: attachment; filename="' . $baseFilename . '.docx"');
                            header('Content-Length: ' . strlen($binary));
                            header('X-Content-Type-Options: nosniff');
                            echo $binary;
                            @unlink($docxPath);
                            @unlink($tmpFile);
                            exit;
                        }
                    }
                } catch (\Throwable $e) {
                    // Fallback below
                }
                @unlink($docxPath);
                @unlink($tmpFile);
            }
        }

        // Fallback: Word-compatible HTML document.
        $html = $this->buildRecipeExportHtml($payload);
        header('Content-Type: application/msword; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $baseFilename . '.doc"');
        header('X-Content-Type-Options: nosniff');
        echo $html;
        exit;
    }

    private function downloadRecipePdf(string $baseFilename, array $payload): void
    {
        $this->tryLoadVendorAutoload();
        if (class_exists('\\Dompdf\\Dompdf')) {
            try {
                $dompdf = new \Dompdf\Dompdf([
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                ]);
                $dompdf->loadHtml($this->buildRecipeExportHtml($payload), 'UTF-8');
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdfBinary = $dompdf->output();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $baseFilename . '.pdf"');
                header('Content-Length: ' . strlen($pdfBinary));
                header('X-Content-Type-Options: nosniff');
                echo $pdfBinary;
                exit;
            } catch (\Throwable $e) {
                // Fallback below
            }
        }

        $text = $this->buildRecipeExportText($payload);
        $pdf = $this->buildSimplePdfFromText($text);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $baseFilename . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');
        echo $pdf;
        exit;
    }

    private function tryLoadVendorAutoload(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $paths = [
            APPROOT . '/vendor/autoload.php',
            APPROOT . '/../vendor/autoload.php',
        ];
        foreach ($paths as $path) {
            if (is_file($path)) {
                require_once $path;
                break;
            }
        }
        $loaded = true;
    }

    private function buildSimplePdfFromText(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $rawLines = explode("\n", $normalized);
        $lines = [];

        foreach ($rawLines as $line) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $line);
            if (!is_string($ascii)) {
                $ascii = '';
            }
            $ascii = preg_replace('/[^\x20-\x7E]/', '', $ascii) ?? '';
            if ($ascii === '') {
                $lines[] = '';
                continue;
            }
            while (strlen($ascii) > 95) {
                $chunk = substr($ascii, 0, 95);
                $splitPos = strrpos($chunk, ' ');
                if ($splitPos === false || $splitPos < 30) {
                    $splitPos = 95;
                }
                $lines[] = rtrim(substr($ascii, 0, $splitPos));
                $ascii = ltrim(substr($ascii, $splitPos));
            }
            $lines[] = $ascii;
        }

        if ($lines === []) {
            $lines = ['Recipe export'];
        }

        $maxLinesPerPage = 52;
        $lineHeight = 14;
        $startY = 800;
        $pages = array_chunk($lines, $maxLinesPerPage);

        $objects = [];
        $appendObject = static function (array &$bucket, string $content): int {
            $bucket[] = $content;
            return count($bucket);
        };

        $catalogNum = $appendObject($objects, '');
        $pagesNum = $appendObject($objects, '');
        $fontNum = $appendObject($objects, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
        $pageNums = [];

        foreach ($pages as $pageLines) {
            $commands = ["BT", "/F1 11 Tf", "50 " . $startY . " Td", $lineHeight . " TL"];
            foreach ($pageLines as $line) {
                $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
                $commands[] = '(' . $safe . ') Tj';
                $commands[] = 'T*';
            }
            $commands[] = 'ET';

            $stream = implode("\n", $commands) . "\n";
            $contentNum = $appendObject(
                $objects,
                '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream"
            );

            $pageNum = $appendObject(
                $objects,
                '<< /Type /Page /Parent ' . $pagesNum . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontNum . ' 0 R >> >> /Contents ' . $contentNum . ' 0 R >>'
            );
            $pageNums[] = $pageNum;
        }

        if ($pageNums === []) {
            $stream = "BT\n/F1 11 Tf\n50 800 Td\n14 TL\n(Recipe export) Tj\nET\n";
            $contentNum = $appendObject(
                $objects,
                '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream"
            );
            $pageNum = $appendObject(
                $objects,
                '<< /Type /Page /Parent ' . $pagesNum . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontNum . ' 0 R >> >> /Contents ' . $contentNum . ' 0 R >>'
            );
            $pageNums[] = $pageNum;
        }

        $kids = implode(' ', array_map(static fn (int $n): string => $n . ' 0 R', $pageNums));
        $objects[$pagesNum - 1] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageNums) . ' >>';
        $objects[$catalogNum - 1] = '<< /Type /Catalog /Pages ' . $pagesNum . ' 0 R >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        $count = count($objects);

        for ($i = 1; $i <= $count; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . " 0 obj\n" . $objects[$i - 1] . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . ($count + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $count; $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . ($count + 1) . " /Root " . $catalogNum . " 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function isVisibleToViewer(array $recipe, bool $isOwner, bool $isAdminUser): bool
    {
        if ($isOwner || $isAdminUser) {
            return true;
        }

        $status = (string) ($recipe['status'] ?? 'approved');
        if ($status !== 'approved') {
            return false;
        }

        if (array_key_exists('user_state', $recipe)) {
            $state = (string) ($recipe['user_state'] ?? '');
            if ($state !== '' && $state !== 'published') {
                return false;
            }
        }

        return true;
    }

    private function labelForRecipe(array $recipe): string
    {
        $status = (string) ($recipe['status'] ?? '');
        $state = (string) ($recipe['user_state'] ?? '');

        if ($status === 'approved' && ($state === '' || $state === 'published')) {
            return 'Đã đăng';
        }
        if ($status === 'pending') {
            return 'Chờ đăng';
        }
        if (in_array($status, ['rejected', 'denied'], true)) {
            return 'Từ chối';
        }
        if ($state === 'completed') {
            return 'Hoàn thiện';
        }
        if ($state === 'draft' || $status === 'draft') {
            return 'Nháp';
        }

        return '';
    }

    private function abortIfRecipePostLocked(string $fallbackRedirect): void
    {
        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        $activeLock = $penaltyModel->getActiveRecipePostLock((int) current_user_id());
        if (!$activeLock) {
            return;
        }

        $reason = trim((string) ($activeLock['reason'] ?? 'Vi phạm nội dung cộng đồng'));
        if ($reason === '') {
            $reason = 'Vi phạm nội dung cộng đồng';
        }
        $until = trim((string) ($activeLock['banned_until'] ?? ''));
        $notice = $until !== ''
            ? 'Bạn đang bị khóa đăng bài đến ' . $until . '. Lý do: ' . $reason
            : 'Bạn đang bị khóa đăng bài vĩnh viễn. Lý do: ' . $reason;

        $separator = str_contains($fallbackRedirect, '?') ? '&' : '?';
        $this->redirect($fallbackRedirect . $separator . 'notice=' . rawurlencode($notice));
    }
}
