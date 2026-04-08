<?php
$recipe = is_array($recipe ?? null) ? $recipe : [];

$heroImagePath = (string) ($recipe['image'] ?? '');
$heroHeightClass = 'h-[340px] lg:h-[480px]';
$heroCategory = (string) ($recipe['category_name'] ?? 'Món chính');
$heroBadge2 = (string) ($recipe['difficulty'] ?? 'Dễ');
$heroTitle = (string) ($recipe['title'] ?? '');
$heroAuthor = (string) ($recipe['author_name'] ?? 'Không rõ');
$heroDate = (string) substr((string) ($recipe['created_at'] ?? date('Y-m-d')), 0, 10);

require APPROOT . '/app/views/partials/shared/content_hero.php';
