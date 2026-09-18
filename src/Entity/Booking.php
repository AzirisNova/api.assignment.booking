<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'bookings')]
final class Booking
{
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Slot::class)]
    #[ORM\JoinColumn(name: 'slot_id', referencedColumnName: 'id', nullable: false)]
    private Slot $slot;

    #[ORM\Column(name: 'start_datetime', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $startDatetime;

    #[ORM\Column(name: 'end_datetime', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $endDatetime;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_CONFIRMED;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Slot $slot, \DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        $this->user = $user;
        $this->slot = $slot;
        $this->startDatetime = $start;
        $this->endDatetime = $end;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSlot(): Slot
    {
        return $this->slot;
    }

    public function getStartDatetime(): \DateTimeImmutable
    {
        return $this->startDatetime;
    }

    public function getEndDatetime(): \DateTimeImmutable
    {
        return $this->endDatetime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
    }

    /**
     * @return array{id: ?int, slotId: ?int, start: string, end: string, status: string, createdAt: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slotId' => $this->slot->getId(),
            'start' => $this->startDatetime->format(DATE_ATOM),
            'end' => $this->endDatetime->format(DATE_ATOM),
            'status' => $this->status,
            'createdAt' => $this->createdAt->format(DATE_ATOM),
        ];
    }
}
