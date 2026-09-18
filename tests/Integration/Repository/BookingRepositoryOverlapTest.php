<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Booking;
use App\Entity\Slot;
use App\Repository\BookingRepository;
use App\Tests\Support\BookingFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BookingRepositoryOverlapTest extends KernelTestCase
{
    use BookingFixtures;

    private BookingRepository $repository;
    private EntityManagerInterface $em;
    private Slot $slot;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Booking::class);

        $this->slot = $this->createAndPersistSlot('2026-09-10');
        $this->createConfirmedBooking($this->slot, '2026-09-10 10:00', '2026-09-10 11:00');
    }

    public function testPartialOverlapAtStartOfExistingBookingIsDetected(): void
    {
        $overlap = $this->repository->hasOverlap(
            $this->slot,
            new \DateTimeImmutable('2026-09-10 10:30'),
            new \DateTimeImmutable('2026-09-10 11:30'),
        );
        self::assertTrue($overlap);
    }

    public function testPartialOverlapAtEndOfExistingBookingIsDetected(): void
    {
        $overlap = $this->repository->hasOverlap(
            $this->slot,
            new \DateTimeImmutable('2026-09-10 09:30'),
            new \DateTimeImmutable('2026-09-10 10:30'),
        );
        self::assertTrue($overlap);
    }

    public function testFullOverlapIsDetected(): void
    {
        $overlap = $this->repository->hasOverlap(
            $this->slot,
            new \DateTimeImmutable('2026-09-10 09:30'),
            new \DateTimeImmutable('2026-09-10 11:30'),
        );
        self::assertTrue($overlap);
    }

    public function testBackToBackBookingImmediatelyAfterIsNotAnOverlap(): void
    {
        $overlap = $this->repository->hasOverlap(
            $this->slot,
            new \DateTimeImmutable('2026-09-10 11:00'),
            new \DateTimeImmutable('2026-09-10 11:30'),
        );
        self::assertFalse($overlap);
    }

    public function testBackToBackBookingImmediatelyBeforeIsNotAnOverlap(): void
    {
        $overlap = $this->repository->hasOverlap(
            $this->slot,
            new \DateTimeImmutable('2026-09-10 09:00'),
            new \DateTimeImmutable('2026-09-10 10:00'),
        );
        self::assertFalse($overlap);
    }

    public function testOverlapIsDetectedWhenRequestCarriesNonUtcOffset(): void
    {
        $overlap = $this->repository->hasOverlap(
            $this->slot,
            new \DateTimeImmutable('2026-09-10T11:15:00+01:00'),
            new \DateTimeImmutable('2026-09-10T11:45:00+01:00'),
        );
        self::assertTrue($overlap);
    }

    public function testCancelledBookingDoesNotBlockOverlap(): void
    {
        $cancelledSlot = $this->createAndPersistSlot('2026-09-11');
        $cancelled = $this->createConfirmedBooking($cancelledSlot, '2026-09-11 10:00', '2026-09-11 11:00');
        $cancelled->cancel();
        $this->em->flush();

        $overlap = $this->repository->hasOverlap(
            $cancelledSlot,
            new \DateTimeImmutable('2026-09-11 10:30'),
            new \DateTimeImmutable('2026-09-11 11:30'),
        );
        self::assertFalse($overlap);
    }
}
