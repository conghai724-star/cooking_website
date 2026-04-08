<?php

declare(strict_types=1);

function upload_image(string $fieldName, string $destinationDir): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpName = $_FILES[$fieldName]['tmp_name'];
    $originalName = basename($_FILES[$fieldName]['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($extension, $allowed, true)) {
        return null;
    }

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0775, true);
    }

    $fileName = uniqid('img_', true) . '.' . $extension;
    $targetPath = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return null;
    }

    return $fileName;
}

function upload_image_from_array(string $fieldName, int $index, string $destinationDir): ?string
{
    if (
        !isset($_FILES[$fieldName]['error'][$index]) ||
        $_FILES[$fieldName]['error'][$index] !== UPLOAD_ERR_OK
    ) {
        return null;
    }

    $tmpName = $_FILES[$fieldName]['tmp_name'][$index] ?? '';
    $originalName = basename((string) ($_FILES[$fieldName]['name'][$index] ?? ''));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if ($tmpName === '' || !in_array($extension, $allowed, true)) {
        return null;
    }

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0775, true);
    }

    $fileName = uniqid('step_', true) . '.' . $extension;
    $targetPath = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return null;
    }

    return $fileName;
}
