<?php
// src/Service/LogParserService.php

namespace App\Service;

use App\Entity\LogEntry;
use App\Entity\LogFile;
use App\Service\LogParser\LogParserInterface;
use Doctrine\ORM\EntityManagerInterface;

class LogParserService
{
    /**
     * @var LogParserInterface[]
     */
    private array $parsers = [];

    /**
     * @param iterable<LogParserInterface> $parsers
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        iterable $parsers = [],
        private string $uploadDirectory = 'public/uploads'
    ) {
        foreach ($parsers as $parser) {
            $this->addParser($parser);
        }
    }

    public function addParser(LogParserInterface $parser): void
    {
        $this->parsers[] = $parser;
    }

    /**
     * @throws \Exception
     */
    public function parseLogFile(LogFile $logFile): array
    {
        $filePath = $this->uploadDirectory . '/' . $logFile->getFilename();

        if (!file_exists($filePath)) {
            throw new \Exception('Log file not found: ' . $filePath);
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \Exception('Cannot read log file');
        }

        // Trouver le parser approprié
        $parser = $this->findParser($content);

        // Parser le contenu
        $entries = $parser->parse($content);

        // Sauvegarder en base
        $this->saveLogEntries($logFile, $entries);

        // Mettre à jour le status du fichier
        $logFile->setStatus('processed');
        $this->entityManager->flush();

        return [
            'parser_used' => $parser->getName(),
            'entries_count' => count($entries),
            'entries' => $entries
        ];
    }

    private function findParser(string $content): LogParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($content)) {
                return $parser;
            }
        }

        // Fallback vers le dernier parser (généralement GenericLogParser)
        if (!empty($this->parsers)) {
            return end($this->parsers);
        }

        throw new \Exception('No log parser available');
    }

    private function saveLogEntries(LogFile $logFile, array $entries): void
    {
        foreach ($entries as $entryData) {
            $logEntry = new LogEntry();
            $logEntry->setLogFile($logFile);
            $logEntry->setTimestamp($entryData['timestamp']);
            $logEntry->setLevel($entryData['level']);
            $logEntry->setSeverity($entryData['severity']);
            $logEntry->setMessage($entryData['message']);
            $logEntry->setChannel($entryData['channel'] ?? 'application');
            $logEntry->setRawContent($entryData['raw']);

            if (isset($entryData['context'])) {
                $logEntry->setContext($entryData['context']);
            }

            $this->entityManager->persist($logEntry);
        }

        $this->entityManager->flush();
    }

    public function getAvailableParsers(): array
    {
        return array_map(fn($parser) => $parser->getName(), $this->parsers);
    }
}
