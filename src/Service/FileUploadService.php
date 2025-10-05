<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service for handling file uploads
 *
 * Follows SOLID principles:
 * - Single Responsibility: Only handles file upload logic
 * - Dependency Inversion: Depends on abstractions (SluggerInterface)
 */
class FileUploadService
{
    public function __construct(
        private string $uploadDirectory,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Upload a file and return the new filename
     *
     * @param UploadedFile $file The uploaded file
     * @return string The new filename
     * @throws \Exception If upload fails
     */
    public function upload(UploadedFile $file): string
    {
        // Get original filename without extension
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Slugify the filename to make it safe for filesystem
        $safeFilename = $this->slugger->slug($originalFilename);

        // Create unique filename with timestamp and random string
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            // Move the file to the upload directory
            $file->move($this->uploadDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \Exception('Failed to upload file: ' . $e->getMessage());
        }

        return $newFilename;
    }

    /**
     * Get the upload directory path
     *
     * @return string
     */
    public function getUploadDirectory(): string
    {
        return $this->uploadDirectory;
    }

    /**
     * Delete a file from the upload directory
     *
     * @param string $filename
     * @return bool
     */
    public function delete(string $filename): bool
    {
        $filePath = $this->uploadDirectory . '/' . $filename;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Check if a file exists in the upload directory
     *
     * @param string $filename
     * @return bool
     */
    public function fileExists(string $filename): bool
    {
        $filePath = $this->uploadDirectory . '/' . $filename;
        return file_exists($filePath);
    }

    /**
     * Get the full path to a file
     *
     * @param string $filename
     * @return string
     */
    public function getFilePath(string $filename): string
    {
        return $this->uploadDirectory . '/' . $filename;
    }
}
