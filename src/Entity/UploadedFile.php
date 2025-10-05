<?php

namespace App\Entity;

use App\Repository\UploadedFileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity to track uploaded files and prevent duplicates
 *
 * Design Pattern: Value Object - Represents an immutable file upload record
 */
#[ORM\Entity(repositoryClass: UploadedFileRepository::class)]
#[ORM\Table(name: 'uploaded_file')]
#[ORM\Index(columns: ['file_hash'], name: 'idx_file_hash')]
class UploadedFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    private ?string $fileHash = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $storedFilename = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $fileSize = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $entriesCount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $uploadedAt = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getFileHash(): ?string
    {
        return $this->fileHash;
    }

    public function setFileHash(string $fileHash): self
    {
        $this->fileHash = $fileHash;
        return $this;
    }

    public function getStoredFilename(): ?string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(string $storedFilename): self
    {
        $this->storedFilename = $storedFilename;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getEntriesCount(): ?int
    {
        return $this->entriesCount;
    }

    public function setEntriesCount(int $entriesCount): self
    {
        $this->entriesCount = $entriesCount;
        return $this;
    }

    public function getUploadedAt(): ?\DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeInterface $uploadedAt): self
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }
}
