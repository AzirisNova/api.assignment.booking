<?php

namespace App\Entity;

use App\Repository\SlotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SlotRepository::class)]
#[ORM\Table(name: 'slots')]
#[ORM\Index(columns: ['date', 'is_active'], name: 'idx_slots_date_active')]
final class Slot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(name: 'start_time', type: 'time_immutable')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(name: 'end_time', type: 'time_immutable')]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(name: 'is_active', type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(\DateTimeImmutable $date, \DateTimeImmutable $startTime, \DateTimeImmutable $endTime)
    {
        $this->date = $date;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return array{id: ?int, date: string, startTime: string, endTime: string, isActive: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'startTime' => $this->startTime->format('H:i:s'),
            'endTime' => $this->endTime->format('H:i:s'),
            'isActive' => $this->isActive,
        ];
    }
}
