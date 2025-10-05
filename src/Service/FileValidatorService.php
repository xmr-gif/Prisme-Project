<?php
// src/Service/FileValidatorService.php

namespace App\Service;

use App\Exception\FileValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileValidatorService
{
    private const MAX_SIZE = 10485760; // 10MB
    private const ALLOWED_EXTENSIONS = ['log', 'txt'];
    private const ALLOWED_MIME_TYPES = [
        'text/plain',
        'text/x-log',
        'application/octet-stream'
    ];

    /**
     * @throws FileValidationException
     */
    public function validate(UploadedFile $file): void
    {
        $this->validateSize($file);
        $this->validateExtension($file);
        $this->validateMimeType($file);
        $this->validateContent($file);
    }

    private function validateSize(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_SIZE) {
            throw new FileValidationException(
                sprintf('File too large. Maximum size is %s.', $this->formatFileSize(self::MAX_SIZE))
            );
        }
    }

    private function validateExtension(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new FileValidationException(
                sprintf('Invalid file type. Only %s files are allowed.', implode(', ', self::ALLOWED_EXTENSIONS))
            );
        }
    }

    private function validateMimeType(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new FileValidationException(
                'Invalid file type. Please upload a valid log file.'
            );
        }
    }

    private function validateContent(UploadedFile $file): void
    {
        $handle = fopen($file->getPathname(), 'r');
        if ($handle) {
            $firstBytes = fread($handle, 1024);
            fclose($handle);

            // Vérifier que c'est du texte
            if (!mb_check_encoding($firstBytes, 'UTF-8') && !mb_check_encoding($firstBytes, 'ASCII')) {
                throw new FileValidationException('File content is not valid text format.');
            }
        }
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
