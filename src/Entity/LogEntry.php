<?php

namespace App\Entity;

use App\Repository\LogEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogEntryRepository::class)]
#[ORM\Index(columns: ['timestamp'])]
#[ORM\Index(columns: ['severity'])]
class LogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LogFile $logFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(length: 20)]
    private ?string $level = null;

    #[ORM\Column(length: 20)]
    private ?string $severity = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $channel = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rawContent = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogFile(): ?LogFile
    {
        return $this->logFile;
    }

    public function setLogFile(?LogFile $logFile): static
    {
        $this->logFile = $logFile;
        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): static
    {
        $this->severity = $severity;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(?string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function getRawContent(): ?string
    {
        return $this->rawContent;
    }

    public function setRawContent(?string $rawContent): static
    {
        $this->rawContent = $rawContent;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): static
    {
        $this->context = $context;
        return $this;
    }

    public function getFormattedTimestamp(): string
    {
        return $this->timestamp->format('Y-m-d H:i:s');
    }

    public function getSeverityBadgeClass(): string
    {
        return match($this->severity) {
            'Critical' => 'badge-critical',
            'Medium' => 'badge-medium',
            'Low' => 'badge-low',
            'Bug' => 'badge-bug',
            default => 'badge-default'
        };
    }
}
