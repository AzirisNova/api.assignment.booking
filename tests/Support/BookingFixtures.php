<?php

namespace App\Tests\Support;

use App\Entity\Booking;
use App\Entity\Slot;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait BookingFixtures
{
    private function em(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    private function futureDate(string $modifier = '+30 days'): string
    {
        return (new \DateTimeImmutable($modifier))->format('Y-m-d');
    }

    private function createAndPersistSlot(string $date): Slot
    {
        $slot = new Slot(
            new \DateTimeImmutable($date),
            new \DateTimeImmutable('09:00'),
            new \DateTimeImmutable('17:00'),
        );

        $this->em()->persist($slot);
        $this->em()->flush();

        return $slot;
    }

    private function createAndPersistUser(?string $email = null): User
    {
        $user = new User(
            $email ?? sprintf('user-%s@example.com', bin2hex(random_bytes(4))),
            password_hash('not-used-directly', PASSWORD_BCRYPT),
            'Test User',
        );

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    private function createConfirmedBooking(Slot $slot, string $start, string $end, ?User $user = null): Booking
    {
        $booking = new Booking(
            $user ?? $this->createAndPersistUser(),
            $slot,
            new \DateTimeImmutable($start),
            new \DateTimeImmutable($end),
        );

        $this->em()->persist($booking);
        $this->em()->flush();

        return $booking;
    }

    private function loginAsTestUser(KernelBrowser $client): User
    {
        $user = $this->createAndPersistUser();
        $user->setApiToken(bin2hex(random_bytes(16)));
        $this->em()->flush();

        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$user->getApiToken());

        return $user;
    }
}
