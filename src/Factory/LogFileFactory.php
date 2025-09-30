<?php
// src/Factory/LogFileFactory.php

namespace App\Factory;

use App\Entity\LogFile;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class LogFileFactory
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}

    public function createFromUploadedFile(UploadedFile $uploadedFile, ?User $user = null): LogFile
    {
        $logFile = new LogFile();

        // Générer un nom de fichier sécurisé
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

        // Configurer l'entité LogFile
        $logFile->setFilename($newFilename);
        $logFile->setOriginalName($uploadedFile->getClientOriginalName());
        $logFile->setFileSize($uploadedFile->getSize());
        $logFile->setMimeType($uploadedFile->getMimeType());
        $logFile->setUser($user);
        $logFile->setStatus('uploaded');

        return $logFile;
    }

    public function createEmpty(): LogFile
    {
        return new LogFile();
    }
}
