<?php

namespace App\Repository;

use App\Entity\UploadedFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UploadedFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UploadedFile::class);
    }

    public function findByHash(string $hash): ?UploadedFile
    {
        return $this->findOneBy(['fileHash' => $hash]);
    }

    public function save(UploadedFile $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
