<?php

declare(strict_types=1);

class RecipeController extends Controller
{
    public function index(): void
    {
        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $perPage = 6;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = $recipeModel->countApproved($keyword !== '' ? $keyword : null);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $recipes = $recipeModel->allApprovedPaged($perPage, $offset, $keyword !== '' ? $keyword : null);

        $this->view('recipes/index', [
            'title' => 'CA�¿½ng th?c',
            'useRecipeHubLayout' => true,
            'recipes' => $recipes,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
        ]);
    }

    public function show(string $id): void
    {
        $recipeId = (int) $id;
        if ($recipeId <= 0) {
            $this->renderNotFound('KhA�¿½ng tA�¿½m th?y cA�¿½ng th?c.');
            return;
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $recipe = $recipeModel->findById($recipeId);
        if (!$recipe) {
            $this->renderNotFound('KhA�¿½ng tA�¿½m th?y cA�¿½ng th?c.');
            return;
        }

        $viewerId = (int) (current_user_id() ?? 0);
        $isOwner = $viewerId > 0 && $viewerId === (int) ($recipe['user_id'] ?? 0);
        $isAdminUser = is_admin();

        if (!$this->isVisibleToViewer($recipe, $isOwner, $isAdminUser)) {
            $this->renderNotFound('KhA�¿½ng tA�¿½m th?y cA�¿½ng th?c.');
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
            'title' => $recipe['title'] ?? 'Cong thuc',
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
                $error = 'Vui lA�¿½ng nh?p tiA�¿½u d? vA�¿½ mA�¿½ t?.';
            } else {
                if ($title === '') {
                    $title = 'Cong thuc nhap';
                }
                if ($description === '') {
                    $description = 'Dang cap nhat.';
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
                    $error = 'KhA�¿½ng th? t?o cA�¿½ng th?c.';
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

                    $this->redirect('/recipes/my?group=all');
                }
            }
        }

        $this->view('recipes/create', [
            'title' => 'A�¿½ang cA�¿½ng th?c',
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
                $error = 'Vui lA�¿½ng nh?p tiA�¿½u d? vA�¿½ mA�¿½ t?.';
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
            'title' => 'Ch?nh s?a cA�¿½ng th?c',
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
            $item['label'] = 'A�¿½A�¿½ luu';
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
            'title' => 'Cong thuc cua toi',
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
            $this->jsonError('BAD_REQUEST', 'KhA�¿½ng tA�¿½m th?y cA�¿½ng th?c.', 400);
        }

        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        $ok = $recipeModel->toggleSave((int) current_user_id(), $recipeId);
        if (!$ok) {
            $this->jsonError('SERVER_ERROR', 'KhA�¿½ng th? c?p nh?t.', 500);
        }

        $saved = $recipeModel->isSaved((int) current_user_id(), $recipeId);
        $this->jsonResponse([
            'success' => true,
            'saved' => $saved,
            'message' => $saved ? 'A�¿½A�¿½ luu cA�¿½ng th?c.' : 'A�¿½A�¿½ b? luu cA�¿½ng th?c.',
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
                $this->jsonError('CONFLICT', 'B?n dA�¿½ bA�¿½o cA�¿½o cA�¿½ng th?c nA�¿½y.', 409);
            }
            $this->redirect('/recipes/' . $recipeId . '?notice=recipe_reported_exists');
        }

        /** @var NotificationModel $notificationModel */
        $notificationModel = $this->model('NotificationModel');
        $notificationModel->createForAdmins(
            'report_recipe',
            'CA�¿½ bA�¿½o cA�¿½o cA�¿½ng th?c m?i (ID: ' . $recipeId . ').'
        );

        if ($isAjax) {
            $this->jsonSuccess([], 'A�¿½A�¿½ g?i bA�¿½o cA�¿½o.', 201);
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
            $this->jsonError('BAD_REQUEST', 'KhA�¿½ng th? theo dA�¿½i.', 400);
        }

        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        if ($penaltyModel->getActiveFollowLock($currentUserId) !== null) {
            $this->jsonError('FOLLOW_LOCKED', 'TA�¿½i kho?n dang b? khA�¿½a theo dA�¿½i t?m th?i.', 403);
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
                $actorName . ' dA�¿½ theo dA�¿½i b?n.',
                '/users/' . $currentUserId
            );
        }
        $this->jsonResponse([
            'success' => true,
            'following' => $followingNow,
            'message' => $followingNow ? 'A�¿½A�¿½ theo dA�¿½i.' : 'A�¿½A�¿½ h?y theo dA�¿½i.',
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
            return 'A�¿½A�¿½ dang';
        }
        if ($status === 'pending') {
            return 'Ch? dang';
        }
        if (in_array($status, ['rejected', 'denied'], true)) {
            return 'T? ch?i';
        }
        if ($state === 'completed') {
            return 'HoA�¿½n thi?n';
        }
        if ($state === 'draft' || $status === 'draft') {
            return 'NhA�¿½p';
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

        $reason = trim((string) ($activeLock['reason'] ?? 'Vi ph?m n?i dung c?ng d?ng'));
        if ($reason === '') {
            $reason = 'Vi ph?m n?i dung c?ng d?ng';
        }
        $until = trim((string) ($activeLock['banned_until'] ?? ''));
        $notice = $until !== ''
            ? 'B?n dang b? khA�¿½a dang bA�¿½i d?n ' . $until . '. LA�¿½ do: ' . $reason
            : 'B?n dang b? khA�¿½a dang bA�¿½i vinh vi?n. LA�¿½ do: ' . $reason;

        $separator = str_contains($fallbackRedirect, '?') ? '&' : '?';
        $this->redirect($fallbackRedirect . $separator . 'notice=' . rawurlencode($notice));
    }
}

