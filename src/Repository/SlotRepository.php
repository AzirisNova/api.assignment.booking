<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Slot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slot>
 */
final class SlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slot::class);
    }

    /**
     * @return list<Slot>
     */
    public function findAvailableForDate(\DateTimeImmutable $date): array
    {
        $dql = 'SELECT s FROM App\Entity\Slot s
                WHERE s.date = :date
                AND s.isActive = true
                AND NOT EXISTS (
                    SELECT b.id FROM App\Entity\Booking b
                    WHERE b.slot = s AND b.status = :status
                )
                ORDER BY s.startTime ASC';

        return $this->getEntityManager()->createQuery($dql)
            ->setParameter('date', $date)
            ->setParameter('status', Booking::STATUS_CONFIRMED)
            ->getResult();
    }
}
