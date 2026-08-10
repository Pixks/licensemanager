<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class UploadService
{
    public function __construct(private readonly string $storagePath, private readonly int $maxBytes) {}
    public function storeZip(array $uploadedFile, string $productSlug, string $version): array
    {
        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('ZIP upload failed.');
        $tmpPath = (string) ($uploadedFile['tmp_name'] ?? '');
        $originalName = (string) ($uploadedFile['name'] ?? 'package.zip');
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip') throw new RuntimeException('Only ZIP archives are allowed.');
        if ((int) ($uploadedFile['size'] ?? 0) > $this->maxBytes) throw new RuntimeException('ZIP archive exceeds the configured size limit.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath) ?: '';
        if (!in_array($mime, ['application/zip', 'application/x-zip', 'application/x-zip-compressed'], true)) throw new RuntimeException('Uploaded file is not a valid ZIP archive.');
        $targetDirectory = rtrim($this->storagePath, '/') . '/packages/' . preg_replace('/[^a-z0-9_-]/i', '-', $productSlug);
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) throw new RuntimeException('Failed to create package storage directory.');
        $targetPath = $targetDirectory . '/' . $version . '-' . time() . '.zip';
        if (!move_uploaded_file($tmpPath, $targetPath) && !rename($tmpPath, $targetPath)) throw new RuntimeException('Failed to move uploaded ZIP archive.');
        return ['zip_path' => $targetPath, 'sha256_hash' => hash_file('sha256', $targetPath)];
    }
}
