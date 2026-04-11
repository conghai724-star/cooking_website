<?php
$recipe = is_array($recipe ?? null) ? $recipe : [];

$heroImagePath = (string) ($recipe['image'] ?? '');
$heroHeightClass = 'h-[340px] lg:h-[480px]';
$heroCategory = (string) ($recipe['category_name'] ?? 'Món chính');
$heroDate = (string) substr((string) ($recipe['created_at'] ?? date('Y-m-d')), 0, 10);

$difficultyLabels = [
    'easy' => 'Dễ',
    'medium' => 'Trung bình',
    'hard' => 'Khó',
];

$heroBadge2 = htmlspecialchars($difficultyLabels[strtolower((string) ($recipe['difficulty'] ?? ''))] ?? (string) ($recipe['difficulty'] ?? 'Dễ'), ENT_QUOTES, 'UTF-8');

require APPROOT . '/app/views/partials/shared/content_hero.php';
