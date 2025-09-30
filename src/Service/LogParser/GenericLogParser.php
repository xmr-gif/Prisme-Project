<?php
// src/Service/LogParser/GenericLogParser.php

namespace App\Service\LogParser;

class GenericLogParser implements LogParserInterface
{
    public function supports(string $content): bool
    {
        // Fallback parser - accepte tout
        return true;
    }

    public function parse(string $content): array
    {
        $lines = explode("\n", $content);
        $entries = [];

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $entry = $this->parseLine($line, $index + 1);
            if ($entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function parseLine(string $line, int $lineNumber): array
    {
        // Essayer de détecter un timestamp au début
        $timestamp = $this->extractTimestamp($line);
        $severity = $this->detectSeverity($line);

        return [
            'timestamp' => $timestamp ?: date('c'),
            'date' => $timestamp ? $this->formatDate($timestamp) : date('Y-m-d H:i:s'),
            'channel' => 'application',
            'severity' => $severity,
            'level' => $severity,
            'message' => $line,
            'raw' => $line,
            'line_number' => $lineNumber
        ];
    }

    private function extractTimestamp(string $line): ?string
    {
        // Différents formats de timestamp
        $patterns = [
            '/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/', // 2024-01-15 10:00:00
            '/^\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2}/', // 15/01/2024 10:00:00
            '/^\w{3}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2}/', // Jan 15 10:00:00
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    private function detectSeverity(string $line): string
    {
        $line = strtolower($line);

        if (preg_match('/\b(critical|fatal|emergency|alert)\b/', $line)) {
            return 'Critical';
        }
        if (preg_match('/\b(error|fail|exception)\b/', $line)) {
            return 'Critical';
        }
        if (preg_match('/\b(warning|warn)\b/', $line)) {
            return 'Medium';
        }
        if (preg_match('/\b(debug|trace)\b/', $line)) {
            return 'Bug';
        }

        return 'Low'; // Default
    }

    private function formatDate(string $timestamp): string
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);
        if (!$date) {
            $date = new \DateTime($timestamp);
        }
        return $date ? $date->format('Y-m-d H:i:s') : $timestamp;
    }

    public function getName(): string
    {
        return 'Generic';
    }
}
