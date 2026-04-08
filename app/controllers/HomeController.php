<?php

declare(strict_types=1);

class HomeController extends Controller
{
    public function index(): void
    {
        /** @var RecipeModel $recipeModel */
        $recipeModel = $this->model('RecipeModel');
        /** @var HomeContentModel $homeContentModel */
        $homeContentModel = $this->model('HomeContentModel');

        $recipes = [];
        try {
            $recipes = $recipeModel->all('approved');
        } catch (Throwable $e) {
            $recipes = [];
        }

        $banner = $homeContentModel->getActiveBanner();
        $featured = $homeContentModel->getFeaturedRecipes(6);
        $recipeOfDay = $homeContentModel->getRecipeOfDay();
        $todayMealPlans = [];
        if (is_logged_in()) {
            /** @var MealPlanModel $mealPlanModel */
            $mealPlanModel = $this->model('MealPlanModel');
            $today = (new DateTimeImmutable('today'))->format('Y-m-d');
            $todayMealPlans = $mealPlanModel->getWeeklyPlan((int) current_user_id(), $today, $today, false);
        }

        if ($featured === []) {
            $featured = array_slice($recipes, 0, 6);
        }

        $this->view('home/index', [
            'title' => 'Khám phá công thức',
            'useRecipeHubLayout' => true,
            'recipes' => $recipes,
            'featured' => $featured,
            'banner' => $banner,
            'recipeOfDay' => $recipeOfDay,
            'todayMealPlans' => $todayMealPlans,
        ]);
    }
}
