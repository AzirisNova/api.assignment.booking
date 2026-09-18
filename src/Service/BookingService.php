<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Slot;
use App\Entity\User;
use App\Exception\OverlappingBookingException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class BookingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookingValidator $validator,
    ) {
    }

    public function create(User $user, Slot $slot, \DateTimeImmutable $start, \DateTimeImmutable $end): Booking
    {
        return $this->em->wrapInTransaction(function () use ($user, $slot, $start, $end) {
            $this->em->lock($slot, LockMode::PESSIMISTIC_WRITE);
            $this->validator->validate($slot, $start, $end, new \DateTimeImmutable());

            $booking = new Booking($user, $slot, $start, $end);
            $this->em->persist($booking);

            try {
                $this->em->flush();
            } catch (DriverException $e) {
                if (in_array($e->getSQLState(), ['23505', '23P01'], true)) {
                    throw new OverlappingBookingException(previous: $e);
                }

                throw $e;
            }

            return $booking;
        });
    }
}
