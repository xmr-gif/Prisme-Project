<?php

namespace App\Repository;

use App\Entity\LogEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for LogEntry entity
 *
 * Design Pattern: Repository Pattern
 * - Encapsulates data access logic
 * - Provides clean interface for querying log entries
 * - Follows Single Responsibility Principle
 */
class LogEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogEntry::class);
    }

    /**
     * Find log entries with filters
     *
     * @param array $filters Array with keys: level, message, dateFrom, dateTo, source
     * @return LogEntry[]
     */
    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('l');

        // Filter by level
        if (!empty($filters['level'])) {
            $qb->andWhere('l.level = :level')
               ->setParameter('level', $filters['level']);
        }

        // Filter by message (partial match)
        if (!empty($filters['message'])) {
            $qb->andWhere('l.message LIKE :message')
               ->setParameter('message', '%' . $filters['message'] . '%');
        }

        // Filter by source
        if (!empty($filters['source'])) {
            $qb->andWhere('l.source = :source')
               ->setParameter('source', $filters['source']);
        }

        // Filter by date range (from)
        if (!empty($filters['dateFrom'])) {
            try {
                $dateFrom = new \DateTime($filters['dateFrom']);
                $dateFrom->setTime(0, 0, 0); // Start of day
                $qb->andWhere('l.timestamp >= :dateFrom')
                   ->setParameter('dateFrom', $dateFrom);
            } catch (\Exception $e) {
                // Invalid date format, skip filter
            }
        }

        // Filter by date range (to)
        if (!empty($filters['dateTo'])) {
            try {
                $dateTo = new \DateTime($filters['dateTo']);
                $dateTo->setTime(23, 59, 59); // End of day
                $qb->andWhere('l.timestamp <= :dateTo')
                   ->setParameter('dateTo', $dateTo);
            } catch (\Exception $e) {
                // Invalid date format, skip filter
            }
        }

        // Order by most recent first
        $qb->orderBy('l.timestamp', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all log entries ordered by timestamp
     *
     * @return LogEntry[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get distinct log levels
     *
     * @return array
     */
    public function findDistinctLevels(): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('DISTINCT l.level')
            ->orderBy('l.level', 'ASC');

        $results = $qb->getQuery()->getResult();

        // Extract just the level values from the result
        return array_column($results, 'level');
    }

    /**
     * Get distinct sources
     *
     * @return array
     */
    public function findDistinctSources(): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('DISTINCT l.source')
            ->where('l.source IS NOT NULL')
            ->orderBy('l.source', 'ASC');

        $results = $qb->getQuery()->getResult();

        // Extract just the source values from the result
        return array_column($results, 'source');
    }

    /**
     * Get log statistics grouped by level
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('l.level, COUNT(l.id) as count')
            ->groupBy('l.level')
            ->orderBy('count', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Delete log entries older than specified date
     *
     * @param \DateTime $beforeDate
     * @return int Number of deleted entries
     */
    public function deleteOlderThan(\DateTime $beforeDate): int
    {
        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.timestamp < :beforeDate')
            ->setParameter('beforeDate', $beforeDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Count total log entries
     *
     * @return int
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find entries by level
     *
     * @param string $level
     * @return LogEntry[]
     */
    public function findByLevel(string $level): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.level = :level')
            ->setParameter('level', $level)
            ->orderBy('l.timestamp', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent entries (last N entries)
     *
     * @param int $limit
     * @return LogEntry[]
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Save a log entry
     *
     * @param LogEntry $entity
     * @param bool $flush
     * @return void
     */
    public function save(LogEntry $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a log entry
     *
     * @param LogEntry $entity
     * @param bool $flush
     * @return void
     */
    public function remove(LogEntry $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
