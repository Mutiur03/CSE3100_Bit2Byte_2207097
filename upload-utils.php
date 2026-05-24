<?php

function upload_filename_slug(string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim((string) $value, '-');

    return $value !== '' ? $value : bin2hex(random_bytes(8));
}

function save_uploaded_image(string $field_name, string $folder, ?string $preferred_name = null): ?string {
    if (empty($_FILES[$field_name]) || ($_FILES[$field_name]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if ($_FILES[$field_name]['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Image must be 2MB or smaller.');
    }

    $tmp_path = $_FILES[$field_name]['tmp_name'];
    $info = getimagesize($tmp_path);
    if ($info === false) {
        throw new RuntimeException('Uploaded file must be an image.');
    }

    $extensions = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif',
    ];

    if (!isset($extensions[$info[2]])) {
        throw new RuntimeException('Only JPG, PNG, WEBP, or GIF images are allowed.');
    }

    $upload_dir = __DIR__ . '/uploads/' . $folder;
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true)) {
        throw new RuntimeException('Upload folder could not be created.');
    }

    $base_name = $preferred_name !== null && trim($preferred_name) !== ''
        ? upload_filename_slug($preferred_name)
        : bin2hex(random_bytes(16));
    $filename = $base_name . '.' . $extensions[$info[2]];
    $target = $upload_dir . '/' . $filename;
    $counter = 2;
    while (is_file($target)) {
        $filename = $base_name . '-' . $counter . '.' . $extensions[$info[2]];
        $target = $upload_dir . '/' . $filename;
        $counter++;
    }

    if (!move_uploaded_file($tmp_path, $target)) {
        throw new RuntimeException('Image could not be saved.');
    }

    return 'uploads/' . $folder . '/' . $filename;
}
