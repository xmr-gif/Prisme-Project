<?php
// src/Repository/LogEntryRepository.php

namespace App\Repository;

use App\Entity\LogEntry;
use App\Entity\LogFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogEntry::class);
    }

    public function findByLogFileWithFilters(
        LogFile $logFile,
        ?string $search = null,
        ?string $severity = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortBy = 'timestamp',
        string $sortOrder = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('le')
            ->andWhere('le.logFile = :logFile')
            ->setParameter('logFile', $logFile);

        // Filtre par recherche de texte
        if ($search) {
            $qb->andWhere('le.message LIKE :search OR le.channel LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par sévérité
        if ($severity && $severity !== 'all') {
            $qb->andWhere('le.severity = :severity')
               ->setParameter('severity', $severity);
        }

        // Filtre par date
        if ($dateFrom) {
            $qb->andWhere('le.timestamp >= :dateFrom')
               ->setParameter('dateFrom', new \DateTime($dateFrom));
        }

        if ($dateTo) {
            $qb->andWhere('le.timestamp <= :dateTo')
               ->setParameter('dateTo', new \DateTime($dateTo . ' 23:59:59'));
        }

        // Tri
        $validSortFields = ['timestamp', 'severity', 'level', 'channel'];
        $sortBy = in_array($sortBy, $validSortFields) ? $sortBy : 'timestamp';
        $sortOrder = in_array(strtoupper($sortOrder), ['ASC', 'DESC']) ? $sortOrder : 'DESC';

        $qb->orderBy('le.' . $sortBy, $sortOrder);

        return $qb->getQuery()->getResult();
    }

    public function getLogStatistics(LogFile $logFile): array
    {
        $qb = $this->createQueryBuilder('le')
            ->select('le.severity, COUNT(le.id) as count')
            ->andWhere('le.logFile = :logFile')
            ->setParameter('logFile', $logFile)
            ->groupBy('le.severity');

        $results = $qb->getQuery()->getResult();

        $stats = [
            'Critical' => 0,
            'Medium' => 0,
            'Low' => 0,
            'Bug' => 0,
            'total' => 0
        ];

        foreach ($results as $result) {
            $stats[$result['severity']] = $result['count'];
            $stats['total'] += $result['count'];
        }

        return $stats;
    }
}
