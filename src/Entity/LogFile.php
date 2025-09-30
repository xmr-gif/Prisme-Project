<?php

namespace App\Entity;

use App\Repository\LogFileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LogFileRepository::class)]
class LogFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $uploadedAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = 'uploaded';

    #[ORM\ManyToOne(inversedBy: 'logFiles')]
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: LogEntry::class, mappedBy: 'logFile', orphanRemoval: true)]
    private Collection $logEntries;

    #[Assert\NotNull(message: 'Please select a file to upload.')]
    #[Assert\File(
        maxSize: '10M',
        extensions: ['log', 'txt'],
        extensionsMessage: 'Please upload a valid log file. Only .log and .txt files are allowed.'
    )]
    private ?File $file = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
        $this->logEntries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getUploadedAt(): ?\DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeImmutable $uploadedAt): static
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getLogEntries(): Collection
    {
        return $this->logEntries;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;
        return $this;
    }

    public function getFileSizeFormatted(): string
    {
        if (!$this->fileSize) {
            return '0 B';
        }

        $size = $this->fileSize;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
