<?php
$recipe = is_array($recipe ?? null) ? $recipe : [];
$ingredients = is_array($ingredients ?? null) ? $ingredients : [];
$steps = is_array($steps ?? null) ? $steps : [];
?>
<div class="detail-main" style="min-width:0;">
    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-4 text-center">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Chuẩn bị</p>
            <p class="text-lg font-black"><?= (int) ($recipe['prep_time'] ?? 20); ?> phút</p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-4 text-center">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Nấu</p>
            <p class="text-lg font-black"><?= (int) ($recipe['cooking_time'] ?? 45); ?> phút</p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-4 text-center">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Độ khó</p>
            <p class="text-lg font-black"><?= htmlspecialchars((string) ($recipe['difficulty'] ?? 'Dễ'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="rounded-2xl border border-primary/20 bg-primary/10 p-4 text-center">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Khẩu phần</p>
            <p class="text-lg font-black"><?= (int) ($recipe['servings'] ?? 4); ?> người</p>
        </div>
    </div>

    <div class="mb-10">
        <h3 class="mb-4 text-2xl font-black">Mô tả công thức</h3>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 leading-7 text-slate-700">
            <?= nl2br(htmlspecialchars((string) ($recipe['description'] ?? ''), ENT_QUOTES, 'UTF-8')); ?>
        </div>
    </div>

    <div class="mb-10">
        <h3 class="mb-4 text-2xl font-black">Nguyên liệu</h3>
        <?php if (!empty($ingredients)): ?>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <?php foreach ($ingredients as $ingredient): ?>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 hover:border-primary">
                        <input class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary" type="checkbox">
                        <span class="text-slate-700">
                            <?php if (!empty($ingredient['quantity'])): ?>
                                <strong><?= htmlspecialchars((string) $ingredient['quantity'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?= htmlspecialchars((string) ($ingredient['unit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                            <?= htmlspecialchars((string) ($ingredient['ingredient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="rounded-xl border border-slate-200 bg-white p-4 text-slate-500">Chưa có dữ liệu nguyên liệu cho công thức này.</p>
        <?php endif; ?>
    </div>

    <div class="mb-10">
        <h3 class="mb-6 text-2xl font-black">Các bước thực hiện</h3>
        <?php if (!empty($steps)): ?>
            <div class="space-y-8">
                <?php foreach ($steps as $step): ?>
                    <div class="flex gap-4">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary font-black text-white">
                            <?= (int) ($step['step_number'] ?? 0); ?>
                        </div>
                        <div class="flex-1">
                            <p class="mb-3 leading-7 text-slate-700"><?= nl2br(htmlspecialchars((string) ($step['content'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
                            <?php if (!empty($step['image'])): ?>
                                <img class="h-56 w-full rounded-2xl object-cover" src="<?= URLROOT; ?>/uploads/<?= htmlspecialchars((string) $step['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="Step image">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="rounded-xl border border-slate-200 bg-white p-4 text-slate-500">Chưa có dữ liệu các bước nấu cho công thức này.</p>
        <?php endif; ?>
    </div>

    <?php if (is_logged_in() && (int) current_user_id() === (int) ($recipe['user_id'] ?? 0)): ?>
        <a class="inline-block rounded-xl bg-primary px-5 py-3 font-bold text-white hover:bg-primary/90" href="<?= URLROOT; ?>/recipes/<?= (int) ($recipe['id'] ?? 0); ?>/edit">Chỉnh sửa công thức</a>
    <?php endif; ?>

    <div class="mt-8 max-w-3xl">
        <?php
        $commentsRootId = 'recipe-comments-section';
        $commentsTitle = 'Bình luận cộng đồng';
        $contentType = 'recipe';
        $contentId = (int) ($recipe['id'] ?? 0);
        $comments = is_array($comments ?? null) ? $comments : [];
        $showCount = false;
        $allowReply = true;
        $maxReplyDepth = 50;
        $emptyText = 'Chưa có bình luận nào.';
        $formPlaceholder = 'Viết bình luận của bạn...';
        $commentExtraHiddenFields = ['recipe_id' => (string) ((int) ($recipe['id'] ?? 0))];
        require APPROOT . '/app/views/partials/shared/content_comments.php';
        ?>
    </div>
</div>



