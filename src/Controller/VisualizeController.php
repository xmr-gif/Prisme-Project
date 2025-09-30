<?php

namespace App\Controller;

use App\Entity\LogFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VisualizeController extends AbstractController
{
    #[Route('/visualize/{id}', name: 'app_visualize')]
    public function index(LogFile $logFile): Response
    {
        return $this->render('visualize/index.html.twig', [
            'logFile' => $logFile,
            'logEntries' => [],
            'statistics' => [
                'Critical' => 0,
                'Medium' => 0,
                'Low' => 0,
                'Bug' => 0,
                'total' => 0
            ],
            'filters' => [
                'search' => '',
                'severity' => 'all',
                'sort_by' => 'timestamp',
                'sort_order' => 'DESC'
            ],
            'severityOptions' => [
                'all' => 'All Severities',
                'Critical' => 'Critical',
                'Medium' => 'Medium',
                'Low' => 'Low',
                'Bug' => 'Bug'
            ]
        ]);
    }
}
