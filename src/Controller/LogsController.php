<?php

namespace App\Controller;

use App\Repository\LogEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for managing log entries
 *
 * Design Patterns Applied:
 * - MVC Pattern: Separates concerns
 * - Strategy Pattern: Different export strategies
 * - Template Method: Common export flow
 */
class LogsController extends AbstractController
{
    public function __construct(
        private LogEntryRepository $logEntryRepository
    ) {
    }

    #[Route('/logs', name: 'app_logs', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Get all log entries (sorting is handled by JavaScript on the frontend)
        $logEntries = $this->logEntryRepository->findAllOrdered();

        return $this->render('logs/index.html.twig', [
            'logEntries' => $logEntries ?? [],
            'currentFilters' => [],
        ]);
    }

    #[Route('/logs/export/{format}', name: 'app_logs_export', methods: ['GET'])]
    public function export(string $format, Request $request): Response
    {
        // Changed from 'message' to 'search' to match the JavaScript
        $searchTerm = $request->query->get('search', '');

        // Get all entries
        $logEntries = $this->logEntryRepository->findAllOrdered();

        // Filter by search term if provided
        if (!empty($searchTerm)) {
            $logEntries = array_filter($logEntries, function($entry) use ($searchTerm) {
                $searchLower = strtolower($searchTerm);
                $date = $entry->getTimestamp()->format('Y-m-d H:i:s');
                return str_contains(strtolower($entry->getMessage()), $searchLower) ||
                       str_contains(strtolower($entry->getLevel()), $searchLower) ||
                       str_contains(strtolower($date), $searchLower);
            });
        }

        return match($format) {
            'csv' => $this->exportCsv($logEntries),
            'json' => $this->exportJson($logEntries),
            'pdf' => $this->exportPdf($logEntries),
            default => throw $this->createNotFoundException('Invalid export format'),
        };
    }

    /**
     * Export logs as CSV
     * Strategy Pattern: CSV export strategy
     */
    private function exportCsv(array $logEntries): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="logs-' . date('Y-m-d-His') . '.csv"');

        $handle = fopen('php://temp', 'r+');

        // Add BOM for Excel UTF-8 support
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header
        fputcsv($handle, ['Date', 'Severity', 'Message', 'Source']);

        // Data
        foreach ($logEntries as $entry) {
            fputcsv($handle, [
                $entry->getTimestamp()->format('Y-m-d H:i:s'),
                $entry->getLevel(),
                $entry->getMessage(),
                $entry->getSource() ?? 'N/A',
            ]);
        }

        rewind($handle);
        $response->setContent(stream_get_contents($handle));
        fclose($handle);

        return $response;
    }

    /**
     * Export logs as JSON
     * Strategy Pattern: JSON export strategy
     */
    private function exportJson(array $logEntries): Response
    {
        $data = array_map(function($entry) {
            return [
                'date' => $entry->getTimestamp()->format('Y-m-d H:i:s'),
                'severity' => $entry->getLevel(),
                'message' => $entry->getMessage(),
                'source' => $entry->getSource(),
            ];
        }, $logEntries);

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $response = new Response($json);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="logs-' . date('Y-m-d-His') . '.json"');

        return $response;
    }

    /**
     * Export logs as HTML/PDF
     * Strategy Pattern: PDF export strategy
     */
    private function exportPdf(array $logEntries): Response
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Log Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h1 {
                    color: #333;
                    border-bottom: 2px solid #0d6efd;
                    padding-bottom: 10px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 12px;
                    text-align: left;
                }
                th {
                    background-color: #f8f9fa;
                    font-weight: 600;
                    color: #495057;
                }
                tbody tr:hover {
                    background-color: #f8f9fa;
                }
                .badge {
                    display: inline-block;
                    padding: 4px 12px;
                    border-radius: 4px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }
                .critical, .error {
                    background: #ffe5e5;
                    color: #dc3545;
                }
                .warning, .medium {
                    background: #fff3cd;
                    color: #856404;
                }
                .info {
                    background: #d1ecf1;
                    color: #0c5460;
                }
                .low {
                    background: #fff8e1;
                    color: #f57f17;
                }
                .debug, .bug {
                    background: #e9ecef;
                    color: #6c757d;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    color: #6c757d;
                    font-size: 0.9rem;
                }
            </style>
        </head>
        <body>
            <h1>Log Report</h1>
            <p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Total Entries:</strong> ' . count($logEntries) . '</p>

            <table>
                <thead>
                    <tr>
                        <th style="width: 20%">Date</th>
                        <th style="width: 15%">Severity</th>
                        <th style="width: 65%">Message</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($logEntries as $entry) {
            $level = strtolower($entry->getLevel());
            $html .= '<tr>
                <td>' . htmlspecialchars($entry->getTimestamp()->format('Y-m-d H:i:s')) . '</td>
                <td><span class="badge ' . $level . '">' . htmlspecialchars($entry->getLevel()) . '</span></td>
                <td>' . htmlspecialchars($entry->getMessage()) . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
            <div class="footer">
                <p>This report was automatically generated by the Log Viewer Application.</p>
            </div>
        </body>
        </html>';

        $response = new Response($html);
        $response->headers->set('Content-Type', 'text/html; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="logs-' . date('Y-m-d-His') . '.html"');

        return $response;
    }
}
