<?php
// src/Service/ExportService.php

namespace App\Service;

use App\Service\Export\ExporterInterface;
use Symfony\Component\HttpFoundation\Response;

class ExportService
{
    /**
     * @var ExporterInterface[]
     */
    private array $exporters = [];

    /**
     * @param iterable<ExporterInterface> $exporters
     */
    public function __construct(iterable $exporters = [])
    {
        foreach ($exporters as $exporter) {
            $this->addExporter($exporter);
        }
    }

    public function addExporter(ExporterInterface $exporter): void
    {
        $this->exporters[] = $exporter;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function export(array $logEntries, string $format, string $filename): Response
    {
        $exporter = $this->findExporter($format);

        if (!$exporter) {
            throw new \InvalidArgumentException(sprintf('No exporter found for format "%s"', $format));
        }

        // Nettoyer le nom de fichier
        $cleanFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));

        return $exporter->export($logEntries, $cleanFilename);
    }

    private function findExporter(string $format): ?ExporterInterface
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports($format)) {
                return $exporter;
            }
        }

        return null;
    }

    public function getSupportedFormats(): array
    {
        $formats = [];
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports('pdf')) $formats['PDF'] = 'pdf';
            if ($exporter->supports('csv')) $formats['CSV'] = 'csv';
            if ($exporter->supports('json')) $formats['JSON'] = 'json';
        }
        return $formats;
    }
}
