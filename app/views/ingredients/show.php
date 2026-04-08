<?php
$ingredient = is_array($ingredient ?? null) ? $ingredient : [];
$nutrition = is_array($ingredient['nutrition'] ?? null) ? $ingredient['nutrition'] : [];
$comments = is_array($comments ?? null) ? $comments : [];
$authorUser = is_array($authorUser ?? null) ? $authorUser : null;
$isFollowingAuthor = (bool) ($isFollowingAuthor ?? false);
$isSavedIngredient = (bool) ($isSavedIngredient ?? false);

$ingredientId = (int) ($ingredient['id'] ?? 0);
$name = (string) ($ingredient['name'] ?? 'Nguyên liệu');
$summary = (string) ($ingredient['summary'] ?? '');
$hero = (string) ($ingredient['hero'] ?? '');
$tag = (string) ($ingredient['tag'] ?? 'Nguyên liệu');
$description = (string) ($ingredient['description'] ?? 'Đang cập nhật.');
$usage = (string) ($ingredient['usage'] ?? 'Đang cập nhật.');
$preparation = (string) ($ingredient['preparation'] ?? 'Đang cập nhật.');
$storage = (string) ($ingredient['storage'] ?? 'Đang cập nhật.');
$tip = (string) ($ingredient['tip'] ?? '');
$categoryLabel = (string) ($ingredient['category_name'] ?? 'Chưa phân loại');
$viewCount = (int) ($ingredient['view_count'] ?? 0);
$authorId = (int) ($ingredient['user_id'] ?? 0);
$authorName = (string) (($authorUser['name'] ?? null) ?: ($ingredient['author_name'] ?? 'Không rõ'));
?>

<div class="w-full">
    <div class="mx-auto max-w-6xl px-2 py-4 sm:px-4">
        <?php
        $ingredientHeroImage = $hero;
        $ingredientHeroTitle = $name;
        $ingredientHeroTag = $tag;
        $ingredientHeroSummary = $summary;
        require APPROOT . '/app/views/partials/ingredient/hero.php';
        ?>

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <?php
                $ingredientDescription = $description;
                $ingredientUsage = $usage;
                $ingredientPreparation = $preparation;
                $ingredientStorage = $storage;
                require APPROOT . '/app/views/partials/ingredient/info_card.php';

                $commentsRootId = 'ingredient-comments-section';
                $commentsTitle = 'Bình luận về nguyên liệu';
                $contentType = 'ingredient';
                $contentId = $ingredientId;
                $redirectTo = '/ingredients/' . $ingredientId;
                $showCount = true;
                $allowReply = false;
                $maxReplyDepth = 0;
                $emptyText = 'Chưa có bình luận nào cho nguyên liệu này.';
                $formPlaceholder = 'Chia sẻ ý kiến của bạn...';
                require APPROOT . '/app/views/partials/shared/content_comments.php';
                ?>
            </div>

            <?php
            $sidebarCategory = $categoryLabel;
            $sidebarViews = $viewCount;
            $sidebarAuthor = $authorName;
            $sidebarAuthorId = $authorId;
            $sidebarIsFollowing = $isFollowingAuthor;
            $sidebarIsSaved = $isSavedIngredient;
            $sidebarIngredientId = $ingredientId;
            $sidebarIngredientName = $name;
            $sidebarTip = $tip;
            require APPROOT . '/app/views/partials/ingredient/sidebar.php';
            ?>
        </div>
    </div>
</div>
<?php
$reportModalId = 'ingredient-report-modal';
$reportModalTitle = 'Báo cáo nguyên liệu';
$reportModalAction = URLROOT . '/ingredients/' . $ingredientId . '/report';
$reportModalReasonField = 'reason';
$reportModalDetailsField = 'details';
$reportModalSuccessToast = 'Đã gửi báo cáo nguyên liệu.';
$reportModalErrorToast = 'Không thể gửi báo cáo nguyên liệu.';
$reportModalHiddenFields = ['redirect_to' => '/ingredients/' . $ingredientId];
require APPROOT . '/app/views/partials/shared/content_report_modal.php';
?>
