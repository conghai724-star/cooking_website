<?php
$heroImage = (string) ($heroImage ?? '');
$heroTitle = (string) ($heroTitle ?? '');
$heroCategory = (string) ($heroCategory ?? 'Mẹo nấu ăn');
$heroAuthor = (string) ($heroAuthor ?? 'Tác giả');
$heroDate = (string) ($heroDate ?? date('Y-m-d'));

$heroImagePath = $heroImage;
$heroImageIsAbsolute = str_starts_with($heroImagePath, 'http') || str_starts_with($heroImagePath, '/');
$heroHeightClass = 'h-[300px] lg:h-[420px]';
$heroBadge2 = '';

require APPROOT . '/app/views/partials/shared/content_hero.php';
