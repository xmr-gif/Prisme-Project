<?php
// src/Factory/ResponseFactory.php

namespace App\Factory;

use App\Entity\LogFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResponseFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function createSuccessResponse(LogFile $logFile, string $message = 'File uploaded successfully!'): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => [
                'file_id' => $logFile->getId(),
                'filename' => $logFile->getOriginalName(),
                'size' => $logFile->getFileSizeFormatted(),
                'upload_date' => $logFile->getUploadedAt()->format('Y-m-d H:i:s')
            ],
            'redirect_url' => $this->urlGenerator->generate('app_visualize', ['id' => $logFile->getId()])
        ]);
    }

    public function createErrorResponse(string $error, int $statusCode = 400): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => $error
        ], $statusCode);
    }
}
