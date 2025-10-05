<?php

namespace App\Service;

use App\Entity\LogEntry;
use Doctrine\ORM\EntityManagerInterface;

class LogParserService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Parse a log file and save entries to database
     *
     * @param string $filePath Full path to the log file
     * @param string $source Source identifier for the logs
     * @return int Number of entries parsed
     */
    public function parseLogFile(string $filePath, string $source = 'unknown'): int
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception("Could not open file: {$filePath}");
        }

        $count = 0;
        $batchSize = 100; // Flush every 100 entries for performance

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $logEntry = $this->parseLine($line, $source);

            if ($logEntry) {
                $this->entityManager->persist($logEntry);
                $count++;

                // Flush in batches for better performance
                if ($count % $batchSize === 0) {
                    $this->entityManager->flush();
                }
            }
        }

        fclose($handle);

        // Final flush for remaining entries
        if ($count % $batchSize !== 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Parse a single log line
     *
     * @param string $line The log line to parse
     * @param string $source Source identifier
     * @return LogEntry|null
     */
    private function parseLine(string $line, string $source): ?LogEntry
    {
        // Try to parse common log formats
        $logEntry = new LogEntry();
        $logEntry->setSource($source);

        // Pattern 1: Symfony/Monolog format
        // [2024-01-15 10:30:45] app.ERROR: Something went wrong {"context":"value"} []
        if (preg_match('/^\[([^\]]+)\]\s+\w+\.(\w+):\s+(.+)$/', $line, $matches)) {
            try {
                $logEntry->setTimestamp(new \DateTime($matches[1]));
                $logEntry->setLevel(strtoupper($matches[2]));
                $logEntry->setMessage($matches[3]);
                return $logEntry;
            } catch (\Exception $e) {
                // Invalid date format, try next pattern
            }
        }

        // Pattern 2: Apache/Nginx format
        // 127.0.0.1 - - [15/Jan/2024:10:30:45 +0000] "GET /path HTTP/1.1" 200 1234
        if (preg_match('/^[\d\.]+ - - \[([^\]]+)\] "([^"]+)" (\d+)/', $line, $matches)) {
            try {
                $logEntry->setTimestamp(\DateTime::createFromFormat('d/M/Y:H:i:s O', $matches[1]));
                $logEntry->setLevel($matches[3] >= 400 ? 'ERROR' : 'INFO');
                $logEntry->setMessage($matches[2]);
                return $logEntry;
            } catch (\Exception $e) {
                // Invalid date format, try next pattern
            }
        }

        // Pattern 3: Simple timestamp format
        // 2024-01-15 10:30:45 ERROR Something went wrong
        if (preg_match('/^(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+(\w+)\s+(.+)$/', $line, $matches)) {
            try {
                $logEntry->setTimestamp(new \DateTime($matches[1]));
                $logEntry->setLevel(strtoupper($matches[2]));
                $logEntry->setMessage($matches[3]);
                return $logEntry;
            } catch (\Exception $e) {
                // Invalid date format, try next pattern
            }
        }

        // Pattern 4: ISO 8601 timestamp
        // 2024-01-15T10:30:45+00:00 [ERROR] Something went wrong
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2})\s+\[(\w+)\]\s+(.+)$/', $line, $matches)) {
            try {
                $logEntry->setTimestamp(new \DateTime($matches[1]));
                $logEntry->setLevel(strtoupper($matches[2]));
                $logEntry->setMessage($matches[3]);
                return $logEntry;
            } catch (\Exception $e) {
                // Invalid date format
            }
        }

        // Fallback: treat entire line as message with current timestamp
        $logEntry->setTimestamp(new \DateTime());
        $logEntry->setLevel('INFO');
        $logEntry->setMessage($line);

        return $logEntry;
    }
}
