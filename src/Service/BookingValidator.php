<?php

namespace App\Service;

use App\Entity\Slot;
use App\Exception\BookingInThePastException;
use App\Exception\DurationExceededException;
use App\Exception\InvalidTimeRangeException;
use App\Exception\OverlappingBookingException;
use App\Repository\BookingRepository;

final class BookingValidator
{
    public function __construct(
        private BookingRepository $bookings,
        private int $maxDurationMinutes = 120,
        private int $minDurationMinutes = 15,
    ) {
    }

    public function validate(Slot $slot, \DateTimeImmutable $start, \DateTimeImmutable $end, \DateTimeImmutable $now): void
    {
        if ($start < $now) {
            throw new BookingInThePastException();
        }
        if ($end <= $start) {
            throw new InvalidTimeRangeException('End time must be after start time.');
        }

        $durationMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        if ($durationMinutes > $this->maxDurationMinutes) {
            throw new DurationExceededException($this->maxDurationMinutes);
        }
        if ($durationMinutes < $this->minDurationMinutes) {
            throw new InvalidTimeRangeException('Booking is shorter than the minimum permitted duration.');
        }

        if ($this->bookings->hasOverlap($slot, $start, $end)) {
            throw new OverlappingBookingException();
        }
    }
}
