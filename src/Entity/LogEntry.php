<?php

namespace App\Entity;

use App\Repository\LogEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity representing a log entry
 *
 * Follows best practices:
 * - Proper encapsulation with private properties
 * - Type hints for all properties
 * - Proper Doctrine annotations
 */
#[ORM\Entity(repositoryClass: LogEntryRepository::class)]
#[ORM\Table(name: 'log_entry')]
#[ORM\Index(columns: ['level'], name: 'idx_level')]
#[ORM\Index(columns: ['timestamp'], name: 'idx_timestamp')]
#[ORM\Index(columns: ['source'], name: 'idx_source')]
class LogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private ?string $level = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get a color class based on log level
     * Helper method for UI display
     */
    public function getLevelColorClass(): string
    {
        return match(strtoupper($this->level)) {
            'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'danger',
            'WARNING' => 'warning',
            'INFO', 'NOTICE' => 'info',
            'DEBUG' => 'secondary',
            default => 'primary',
        };
    }

    /**
     * Get badge class for Bootstrap styling
     */
    public function getLevelBadgeClass(): string
    {
        return 'badge bg-' . $this->getLevelColorClass();
    }
}
