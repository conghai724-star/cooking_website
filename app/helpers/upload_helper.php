<?php

declare(strict_types=1);

const UPLOAD_IMAGE_MAX_BYTES = 5 * 1024 * 1024; // 5MB
const UPLOAD_IMAGE_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
const UPLOAD_IMAGE_ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

function validate_uploaded_image(string $tmpName, string $originalName, int $size): ?string
{
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return null;
    }

    if ($size <= 0 || $size > UPLOAD_IMAGE_MAX_BYTES) {
        return null;
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, UPLOAD_IMAGE_ALLOWED_EXTENSIONS, true)) {
        return null;
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $mimeType = (string) finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            if (!in_array($mimeType, UPLOAD_IMAGE_ALLOWED_MIME_TYPES, true)) {
                return null;
            }
        }
    }

    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        return null;
    }

    $imageMime = (string) ($imageInfo['mime'] ?? '');
    if (!in_array($imageMime, UPLOAD_IMAGE_ALLOWED_MIME_TYPES, true)) {
        return null;
    }

    return $extension;
}

function upload_image(string $fieldName, string $destinationDir): ?string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpName = (string) ($_FILES[$fieldName]['tmp_name'] ?? '');
    $originalName = basename((string) ($_FILES[$fieldName]['name'] ?? ''));
    $size = (int) ($_FILES[$fieldName]['size'] ?? 0);
    $extension = validate_uploaded_image($tmpName, $originalName, $size);
    if ($extension === null) {
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
    $size = (int) ($_FILES[$fieldName]['size'][$index] ?? 0);
    $extension = validate_uploaded_image((string) $tmpName, $originalName, $size);

    if ($extension === null) {
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
