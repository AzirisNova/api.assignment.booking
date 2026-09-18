<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Slot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function hasOverlap(Slot $slot, \DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        return (bool) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.slot = :slot')
            ->andWhere('b.status = :status')
            ->andWhere('b.startDatetime < :end')
            ->andWhere('b.endDatetime > :start')
            ->setParameter('slot', $slot)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->setParameter('start', $start, Types::DATETIMETZ_IMMUTABLE)
            ->setParameter('end', $end, Types::DATETIMETZ_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
