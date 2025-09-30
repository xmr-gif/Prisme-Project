<?php
// src/Service/FileUploadService.php

namespace App\Service;

use App\Entity\LogFile;
use App\Factory\LogFileFactory;
use App\Exception\FileUploadException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploadService
{
    public function __construct(
        private LogFileFactory $logFileFactory,
        private EntityManagerInterface $entityManager,
        private FileValidatorService $validator,
        private string $uploadDirectory
    ) {}

    /**
     * @throws FileUploadException
     */
    public function handleUpload(UploadedFile $uploadedFile, $user = null): LogFile
    {
        // 1. Validation
        $this->validator->validate($uploadedFile);

        // 2. Créer l'entité via Factory
        $logFile = $this->logFileFactory->createFromUploadedFile($uploadedFile, $user);

        // 3. Déplacer le fichier
        $this->moveFileToStorage($uploadedFile, $logFile->getFilename());

        // 4. Persister en base
        $this->persistLogFile($logFile);

        return $logFile;
    }

    private function moveFileToStorage(UploadedFile $file, string $filename): void
    {
        try {
            // Créer le dossier si nécessaire
            if (!is_dir($this->uploadDirectory)) {
                mkdir($this->uploadDirectory, 0755, true);
            }

            $file->move($this->uploadDirectory, $filename);
        } catch (FileException $e) {
            throw new FileUploadException('Failed to move uploaded file: ' . $e->getMessage(), 0, $e);
        }
    }

    private function persistLogFile(LogFile $logFile): void
    {
        try {
            $this->entityManager->persist($logFile);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new FileUploadException('Failed to save file information: ' . $e->getMessage(), 0, $e);
        }
    }
}
