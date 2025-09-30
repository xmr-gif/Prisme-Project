<?php
// src/Controller/UploadController.php - VERSION NETTOYÉE

namespace App\Controller;

use App\Entity\LogFile;
use App\Exception\FileUploadException;
use App\Exception\FileValidationException;
use App\Factory\LogFileFactory;
use App\Factory\ResponseFactory;
use App\Form\LogFileType;
use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    public function __construct(
        private LogFileFactory $logFileFactory,
        private FileUploadService $fileUploadService,
        private ResponseFactory $responseFactory
    ) {}

    #[Route('/upload', name: 'app_upload')]
    public function index(Request $request): Response
    {
        $logFile = $this->logFileFactory->createEmpty();
        $form = $this->createForm(LogFileType::class, $logFile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $uploadedFile = $form->get('file')->getData();
                $processedLogFile = $this->fileUploadService->handleUpload($uploadedFile, $this->getUser());

                $this->addFlash('success', 'File uploaded successfully! Size: ' . $processedLogFile->getFileSizeFormatted());

                return $this->redirectToRoute('app_visualize', ['id' => $processedLogFile->getId()]);

            } catch (FileValidationException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (FileUploadException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('upload/index.html.twig', [
            'form' => $form->createView(),
            'controller_name' => 'Upload Your Log File'
        ]);
    }

    #[Route('/upload/ajax', name: 'app_upload_ajax', methods: ['POST'])]
    public function uploadAjax(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile) {
            return $this->responseFactory->createErrorResponse('No file uploaded');
        }

        try {
            $logFile = $this->fileUploadService->handleUpload($uploadedFile, $this->getUser());
            return $this->responseFactory->createSuccessResponse($logFile);

        } catch (FileValidationException $e) {
            return $this->responseFactory->createErrorResponse($e->getMessage());
        } catch (FileUploadException $e) {
            return $this->responseFactory->createErrorResponse($e->getMessage(), 500);
        }
    }

    #[Route('/visualize/{id}', name: 'app_visualize')]
    public function visualize(LogFile $logFile): Response
    {
        return $this->render('visualize/index.html.twig', [
            'logFile' => $logFile
        ]);
    }
}
