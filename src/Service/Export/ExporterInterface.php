<?php
// src/Service/Export/ExporterInterface.php

namespace App\Service\Export;

use Symfony\Component\HttpFoundation\Response;

interface ExporterInterface
{
    public function export(array $logEntries, string $filename): Response;
    public function supports(string $format): bool;
    public function getContentType(): string;
    public function getFileExtension(): string;
}
