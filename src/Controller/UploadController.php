<?php

namespace App\Controller;

use App\Entity\UploadedFile;
use App\Form\LogFileType;
use App\Repository\UploadedFileRepository;
use App\Service\FileUploadService;
use App\Service\LogParserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

/**
 * Controller for file uploads
 *
 * Design Pattern: Facade - Simplifies complex upload and parsing operations
 */
class UploadController extends AbstractController
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private LogParserService $logParserService,
        private EntityManagerInterface $entityManager,
        private UploadedFileRepository $uploadedFileRepository
    ) {
    }

    #[Route('/upload', name: 'app_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request): Response
    {
        $form = $this->createForm(LogFileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SymfonyUploadedFile $logFile */
            $logFile = $form->get('logFile')->getData();

            if ($logFile) {
                try {
                    // IMPORTANT: Calculate hash BEFORE moving the file
                    // because the temporary file will be deleted after move
                    $tempPath = $logFile->getPathname();
                    $fileHash = hash_file('sha256', $tempPath);
                    $originalName = $logFile->getClientOriginalName();
                    $fileSize = $logFile->getSize();

                    // Check if file was already uploaded
                    $existingFile = $this->uploadedFileRepository->findByHash($fileHash);

                    if ($existingFile) {
                        $this->addFlash('info', sprintf(
                            'This file "%s" was already uploaded on %s with %d log entries. No need to upload again!',
                            $existingFile->getOriginalName(),
                            $existingFile->getUploadedAt()->format('Y-m-d H:i:s'),
                            $existingFile->getEntriesCount()
                        ));

                        return $this->redirectToRoute('app_logs');
                    }

                    // Upload the file (this moves it from temp to permanent location)
                    $filename = $this->fileUploadService->upload($logFile);
                    $filePath = $this->fileUploadService->getFilePath($filename);

                    // Parse the log file
                    $entriesCount = $this->logParserService->parseLogFile($filePath, 'uploaded-file');

                    // Track the uploaded file
                    $uploadedFile = new UploadedFile();
                    $uploadedFile->setOriginalName($originalName);
                    $uploadedFile->setFileHash($fileHash);
                    $uploadedFile->setStoredFilename($filename);
                    $uploadedFile->setFileSize($fileSize);
                    $uploadedFile->setEntriesCount($entriesCount);

                    $this->uploadedFileRepository->save($uploadedFile, true);

                    // Flush log entries
                    $this->entityManager->flush();

                    $this->addFlash('success', "Successfully uploaded and parsed {$entriesCount} log entries!");

                    return $this->redirectToRoute('app_logs');

                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error processing file: ' . $e->getMessage());
                    return $this->redirectToRoute('app_upload');
                }
            } else {
                $this->addFlash('error', 'Please select a file to upload.');
                return $this->redirectToRoute('app_upload');
            }
        }

        return $this->render('upload/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
