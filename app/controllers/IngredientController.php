<?php

declare(strict_types=1);

class IngredientController extends Controller
{
    public function index(): void
    {
        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        $ingredientCategories = $categoryModel->byType('ingredient');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $requestedCategoryId = (int) ($_GET['category'] ?? 0);
        $filterCategoryId = null;
        if ($requestedCategoryId > 0) {
            foreach ($ingredientCategories as $row) {
                if ($requestedCategoryId === (int) ($row['id'] ?? 0)) {
                    $filterCategoryId = $requestedCategoryId;
                    break;
                }
            }
        }

        $perPage = 6;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $kw = $keyword !== '' ? $keyword : null;
        $total = $ingredientModel->countByStatus('approved', 'library', $kw, $filterCategoryId);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $ingredients = $ingredientModel->allPaged('approved', 'library', $perPage, $offset, $kw, $filterCategoryId);

        $this->view('ingredients/index', [
            'title' => 'Nguy�n li?u',
            'useRecipeHubLayout' => true,
            'ingredients' => $ingredients,
            'ingredient_categories' => $ingredientCategories,
            'filter_category_id' => $filterCategoryId ?? 0,
            'page' => $page,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
        ]);
    }

    public function show(string $id): void
    {
        $ingredientId = (int) $id;
        if ($ingredientId <= 0) {
            $this->renderNotFound('Kh�ng t�m th?y nguy�n li?u.');
            return;
        }

        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        $ingredient = $ingredientModel->findById($ingredientId);
        if (!$ingredient) {
            $this->renderNotFound('Kh�ng t�m th?y nguy�n li?u.');
            return;
        }

        $viewerId = (int) (current_user_id() ?? 0);
        $isOwner = $viewerId > 0 && $viewerId === (int) ($ingredient['user_id'] ?? 0);

        if (($ingredient['status'] ?? 'approved') !== 'approved' && !is_admin() && !$isOwner) {
            $this->renderNotFound('Kh�ng t�m th?y nguy�n li?u.');
            return;
        }

        $nutritionRow = $ingredientModel->getNutrition($ingredientId) ?: [];
        $nutrition = [
            ['Calories', ($nutritionRow['calories'] ?? null) !== null ? $nutritionRow['calories'] . ' kcal' : 'N/A', 'local_fire_department'],
            ['Total Fat', ($nutritionRow['fat'] ?? null) !== null ? $nutritionRow['fat'] . ' g' : 'N/A', 'opacity'],
            ['Protein', ($nutritionRow['protein'] ?? null) !== null ? $nutritionRow['protein'] . ' g' : 'N/A', 'fitness_center'],
            ['Carbohydrates', ($nutritionRow['carb'] ?? null) !== null ? $nutritionRow['carb'] . ' g' : 'N/A', 'grain'],
        ];

        $hero = (string) ($ingredient['image'] ?? '');
        if ($hero !== '' && !preg_match('/^https?:\/\//i', $hero)) {
            $hero = URLROOT . '/uploads/' . $hero;
        }
        if ($hero === '') {
            $hero = 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=80';
        }

        $ingredient['nutrition'] = $nutrition;
        $ingredient['tag'] = 'Ingredient Spotlight';
        $ingredient['summary'] = $ingredient['description'] ?? '';
        $ingredient['tip'] = $ingredient['usage'] ?? '';
        $ingredient['hero'] = $hero;

        /** @var CommentModel $commentModel */
        $commentModel = $this->model('CommentModel');
        $comments = $commentModel->byIngredient($ingredientId);

        $authorUser = null;
        $isFollowingAuthor = false;
        $isSavedIngredient = false;
        $authorUserId = (int) ($ingredient['user_id'] ?? 0);
        if ($authorUserId > 0) {
            /** @var UserModel $userModel */
            $userModel = $this->model('UserModel');
            $authorUser = $userModel->findById($authorUserId) ?: null;
            if ($viewerId > 0 && $viewerId !== $authorUserId) {
                /** @var FollowModel $followModel */
                $followModel = $this->model('FollowModel');
                $isFollowingAuthor = $followModel->isFollowing($viewerId, $authorUserId);
            }
        }
        if ($viewerId > 0) {
            $isSavedIngredient = $ingredientModel->isSaved($viewerId, $ingredientId);
        }

        $this->view('ingredients/show', [
            'title' => $ingredient['name'],
            'useRecipeHubLayout' => true,
            'ingredient' => $ingredient,
            'comments' => $comments,
            'authorUser' => $authorUser,
            'isFollowingAuthor' => $isFollowingAuthor,
            'isSavedIngredient' => $isSavedIngredient,
        ]);
    }

    public function create(): void
    {
        require_login();
        $this->abortIfIngredientPostLocked('/ingredients');

        $error = '';
        $success = false;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $categoryId = trim((string) ($_POST['category_id'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $usage = trim((string) ($_POST['usage'] ?? ''));
            $preparation = trim((string) ($_POST['preparation'] ?? ''));
            $storage = trim((string) ($_POST['storage'] ?? ''));
            $name = trim(profanity_mask($name));
            $description = trim(profanity_mask($description));
            $usage = trim(profanity_mask($usage));
            $preparation = trim(profanity_mask($preparation));
            $storage = trim(profanity_mask($storage));
            $calories = trim((string) ($_POST['calories'] ?? ''));
            $protein = trim((string) ($_POST['protein'] ?? ''));
            $fat = trim((string) ($_POST['fat'] ?? ''));
            $carb = trim((string) ($_POST['carb'] ?? ''));

            if ($name === '') {
                $error = 'Vui l�ng nh?p t�n nguy�n li?u.';
            } else {
                $categoryValue = $categoryId !== '' ? (int) $categoryId : null;
                $descriptionValue = $description !== '' ? $description : null;
                $usageValue = $usage !== '' ? $usage : null;
                $preparationValue = $preparation !== '' ? $preparation : null;
                $storageValue = $storage !== '' ? $storage : null;
                $caloriesValue = $calories !== '' ? (float) $calories : null;
                $proteinValue = $protein !== '' ? (float) $protein : null;
                $fatValue = $fat !== '' ? (float) $fat : null;
                $carbValue = $carb !== '' ? (float) $carb : null;
                $image = upload_image('image', APPROOT . '/public/uploads');

                /** @var IngredientModel $ingredientModel */
                $ingredientModel = $this->model('IngredientModel');
                try {
                    $id = $ingredientModel->create(
                        $name,
                        $categoryValue,
                        $descriptionValue,
                        $usageValue,
                        $preparationValue,
                        $storageValue,
                        'pending',
                        'library',
                        $image,
                        (int) current_user_id()
                    );
                    if ($id === false) {
                        $error = 'Kh�ng th? g?i nguy�n li?u.';
                    } else {
                        $ingredientModel->upsertNutrition($id, $caloriesValue, $proteinValue, $fatValue, $carbValue);
                        $success = true;
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        $error = 'T�n nguy�n li?u dang b? r�ng bu?c UNIQUE trong CSDL. Vui l�ng x�a UNIQUE ? c?t name.';
                    } else {
                        throw $e;
                    }
                }
            }
        }

        /** @var CategoryModel $categoryModel */
        $categoryModel = $this->model('CategoryModel');
        $categories = $categoryModel->byType('ingredient');

        $this->view('ingredients/create', [
            'title' => 'G�p � nguy�n li?u',
            'useRecipeHubLayout' => true,
            'categories' => $categories,
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function myIngredients(): void
    {
        require_login();

        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        $ingredients = $ingredientModel->byUser((int) current_user_id());

        $this->view('ingredients/my', [
            'title' => 'Nguy�n li?u c?a t�i',
            'useRecipeHubLayout' => true,
            'ingredients' => $ingredients,
            'has_user_column' => !empty($ingredients),
        ]);
    }

    public function resubmit(string $id): void
    {
        require_login();
        $this->abortIfIngredientPostLocked('/ingredients/my');
        $ingredientId = (int) $id;
        if ($ingredientId <= 0) {
            $this->redirect('/ingredients/my');
        }

        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        $ingredientModel->resubmit($ingredientId, (int) current_user_id());
        $this->redirect('/ingredients/my');
    }

    public function report(string $id): void
    {
        require_login();

        $isAjax = $this->isAjaxRequest();
        $ingredientId = (int) $id;
        if ($ingredientId <= 0) {
            if ($isAjax) {
                $this->jsonError('BAD_REQUEST', 'Kh�ng t�m th?y nguy�n li?u.', 400);
            }
            $this->redirect('/ingredients');
        }

        $reason = trim((string) ($_POST['reason'] ?? ''));
        $details = trim((string) (($_POST['details'] ?? '') ?: ($_POST['reason_other'] ?? '')));
        $normalizedReason = strtolower($reason);
        if ($reason !== '' && in_array($normalizedReason, ['kh�c', 'khac'], true) && $details !== '') {
            $reason = $details;
        }
        if ($reason === '') {
            $reason = 'N?i dung nguy�n li?u c� d?u hi?u vi ph?m.';
        }

        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        $ok = $ingredientModel->saveReport((int) current_user_id(), $ingredientId, $reason);
        if ($ok) {
            /** @var NotificationModel $notificationModel */
            $notificationModel = $this->model('NotificationModel');
            $notificationModel->createForAdmins(
                'report_ingredient',
                'C� b�o c�o nguy�n li?u m?i (ID: ' . $ingredientId . ').'
            );
        }

        if ($isAjax) {
            if ($ok) {
                $this->jsonSuccess([], '�� g?i b�o c�o nguy�n li?u.', 201);
            }
            $this->jsonError('CONFLICT', 'B?n d� b�o c�o nguy�n li?u n�y.', 409);
        }

        $this->redirect('/ingredients/' . $ingredientId . '?notice=' . ($ok ? 'ingredient_reported' : 'ingredient_reported_exists'));
    }

    public function save(): void
    {
        require_login();

        $ingredientId = (int) ($_POST['ingredient_id'] ?? 0);
        $redirectTo = trim((string) ($_POST['redirect_to'] ?? '/ingredients'));
        if ($ingredientId <= 0) {
            $this->redirect($redirectTo);
        }

        /** @var IngredientModel $ingredientModel */
        $ingredientModel = $this->model('IngredientModel');
        $ingredientModel->toggleSave((int) current_user_id(), $ingredientId);
        $saved = $ingredientModel->isSaved((int) current_user_id(), $ingredientId);

        $glue = str_contains($redirectTo, '?') ? '&' : '?';
        $this->redirect($redirectTo . $glue . 'notice=' . ($saved ? 'ingredient_saved' : 'ingredient_unsaved'));
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        return strcasecmp($requestedWith, 'XMLHttpRequest') === 0 || str_contains($accept, 'application/json');
    }

    private function abortIfIngredientPostLocked(string $fallbackRedirect): void
    {
        /** @var UserPenaltyModel $penaltyModel */
        $penaltyModel = $this->model('UserPenaltyModel');
        $activeLock = $penaltyModel->getActiveIngredientPostLock((int) current_user_id());
        if (!$activeLock) {
            return;
        }

        $reason = trim((string) ($activeLock['reason'] ?? 'Vi ph?m n?i dung c?ng d?ng'));
        if ($reason === '') {
            $reason = 'Vi ph?m n?i dung c?ng d?ng';
        }
        $until = trim((string) ($activeLock['banned_until'] ?? ''));
        $notice = $until !== ''
            ? 'B?n dang b? kh�a dang nguy�n li?u d?n ' . $until . '. L� do: ' . $reason
            : 'B?n dang b? kh�a dang nguy�n li?u vinh vi?n. L� do: ' . $reason;

        $separator = str_contains($fallbackRedirect, '?') ? '&' : '?';
        $this->redirect($fallbackRedirect . $separator . 'notice=' . rawurlencode($notice));
    }
}
