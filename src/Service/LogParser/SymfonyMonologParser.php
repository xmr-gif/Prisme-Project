<?php
// src/Service/LogParser/SymfonyMonologParser.php

namespace App\Service\LogParser;

class SymfonyMonologParser implements LogParserInterface
{
    public function supports(string $content): bool
    {
        // Détecter le format Symfony/Monolog
        return preg_match('/^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})/', $content);
    }

    public function parse(string $content): array
    {
        $lines = explode("\n", $content);
        $entries = [];

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $entry = $this->parseLine($line, $lineNumber + 1);
            if ($entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function parseLine(string $line, int $lineNumber): ?array
    {
        // Pattern pour Symfony/Monolog
        // [2025-07-03T23:01:41.970023+00:00] doctrine.INFO: Message {"context"} []
        $pattern = '/^\[([^\]]+)\]\s+([^\.]+)\.([A-Z]+):\s+([^{]+)(?:\s*(\{.+\}))?(?:\s*(\[.*\]))?$/';

        if (preg_match($pattern, $line, $matches)) {
            $timestamp = $matches[1];
            $channel = $matches[2];
            $level = $matches[3];
            $message = trim($matches[4]);
            $context = isset($matches[5]) ? $this->parseJson($matches[5]) : null;
            $extra = isset($matches[6]) ? $this->parseJson($matches[6]) : null;

            return [
                'timestamp' => $this->parseTimestamp($timestamp),
                'date' => $this->formatDate($timestamp),
                'channel' => $channel,
                'level' => $level,
                'severity' => $this->mapSeverity($level),
                'message' => $this->cleanMessage($message),
                'context' => $context,
                'extra' => $extra,
                'raw' => $line,
                'line_number' => $lineNumber
            ];
        }

        // Fallback pour lignes non conformes
        return [
            'timestamp' => new \DateTime(),
            'date' => date('Y-m-d H:i:s'),
            'channel' => 'unknown',
            'level' => 'INFO',
            'severity' => 'Low',
            'message' => $line,
            'context' => null,
            'extra' => null,
            'raw' => $line,
            'line_number' => $lineNumber
        ];
    }

    private function parseTimestamp(string $timestamp): \DateTime
    {
        // Gérer différents formats de timestamp
        $formats = [
            \DateTime::ATOM,
            'Y-m-d\TH:i:s.uP',
            'Y-m-d\TH:i:sP',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $timestamp);
            if ($date !== false) {
                return $date;
            }
        }

        // Fallback
        return new \DateTime();
    }

    private function formatDate(string $timestamp): string
    {
        $date = $this->parseTimestamp($timestamp);
        return $date->format('Y-m-d H:i:s');
    }

    private function mapSeverity(string $level): string
    {
        return match(strtoupper($level)) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'Critical',
            'WARNING' => 'Medium',
            'NOTICE', 'INFO' => 'Low',
            'DEBUG' => 'Bug',
            default => 'Low'
        };
    }

    private function cleanMessage(string $message): string
    {
        // Nettoyer le message
        $message = trim($message);

        // Limiter la longueur pour l'affichage
        if (strlen($message) > 200) {
            $message = substr($message, 0, 197) . '...';
        }

        return $message;
    }

    private function parseJson(string $json): ?array
    {
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    public function getName(): string
    {
        return 'Symfony/Monolog';
    }
}
