<?php

namespace App\Tests\Unit\Service;

use App\Entity\Slot;
use App\Exception\BookingInThePastException;
use App\Exception\DurationExceededException;
use App\Exception\InvalidTimeRangeException;
use App\Exception\OverlappingBookingException;
use App\Repository\BookingRepository;
use App\Service\BookingValidator;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

final class BookingValidatorTest extends TestCase
{
    private BookingRepository&Stub $bookings;
    private BookingValidator $validator;
    private Slot $slot;

    protected function setUp(): void
    {
        $this->bookings = $this->createStub(BookingRepository::class);
        $this->validator = new BookingValidator($this->bookings, maxDurationMinutes: 120, minDurationMinutes: 15);

        $this->slot = new Slot(
            new \DateTimeImmutable('2026-09-08'),
            new \DateTimeImmutable('09:00'),
            new \DateTimeImmutable('17:00'),
        );
    }

    public function testValidFutureBookingIsAccepted(): void
    {
        $now = new \DateTimeImmutable('2026-09-07 09:00:00');
        $start = $now->modify('+1 day');
        $end = $start->modify('+30 minutes');
        $this->bookings->method('hasOverlap')->willReturn(false);

        $this->validator->validate($this->slot, $start, $end, $now);
        $this->addToAssertionCount(1);
    }

    public function testPastStartDateIsRejected(): void
    {
        $now = new \DateTimeImmutable('2026-09-07 09:00:00');
        $this->expectException(BookingInThePastException::class);

        $this->validator->validate($this->slot, $now->modify('-1 hour'), $now->modify('+30 minutes'), $now);
    }

    public function testEndBeforeStartIsRejected(): void
    {
        $now = new \DateTimeImmutable('2026-09-07 09:00:00');
        $start = $now->modify('+1 day');
        $this->expectException(InvalidTimeRangeException::class);

        $this->validator->validate($this->slot, $start, $start->modify('-15 minutes'), $now);
    }

    public function testBookingLongerThanPermittedDurationIsRejected(): void
    {
        $now = new \DateTimeImmutable('2026-09-07 09:00:00');
        $start = $now->modify('+1 day');
        $this->expectException(DurationExceededException::class);

        $this->validator->validate($this->slot, $start, $start->modify('+3 hours'), $now);
    }

    public function testBookingShorterThanMinimumDurationIsRejected(): void
    {
        $now = new \DateTimeImmutable('2026-09-07 09:00:00');
        $start = $now->modify('+1 day');
        $this->expectException(InvalidTimeRangeException::class);

        $this->validator->validate($this->slot, $start, $start->modify('+5 minutes'), $now);
    }

    public function testOverlappingBookingIsRejected(): void
    {
        $now = new \DateTimeImmutable('2026-09-07 09:00:00');
        $start = $now->modify('+1 day');
        $end = $start->modify('+30 minutes');
        $this->bookings->method('hasOverlap')->willReturn(true);
        $this->expectException(OverlappingBookingException::class);

        $this->validator->validate($this->slot, $start, $end, $now);
    }
}
