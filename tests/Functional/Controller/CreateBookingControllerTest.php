<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Support\BookingFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateBookingControllerTest extends WebTestCase
{
    use BookingFixtures;

    public function testOverlappingBookingReturns409NotCreated(): void
    {
        $client = static::createClient();
        $this->loginAsTestUser($client);

        $date = $this->futureDate();
        $slot = $this->createAndPersistSlot($date);
        $this->createConfirmedBooking($slot, $date.' 10:00', $date.' 11:00');

        $client->request('POST', '/api/bookings', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'slotId' => $slot->getId(),
            'start' => $date.'T10:30:00+01:00',
            'end' => $date.'T11:30:00+01:00',
        ]));

        self::assertResponseStatusCodeSame(409);
    }

    public function testValidBookingReturns201WithLocationHeader(): void
    {
        $client = static::createClient();
        $this->loginAsTestUser($client);

        $date = $this->futureDate();
        $slot = $this->createAndPersistSlot($date);

        $client->request('POST', '/api/bookings', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'slotId' => $slot->getId(),
            'start' => $date.'T10:00:00+01:00',
            'end' => $date.'T10:30:00+01:00',
        ]));

        self::assertResponseStatusCodeSame(201);
        self::assertTrue($client->getResponse()->headers->has('Location'));
    }

    public function testUnauthenticatedRequestReturns401(): void
    {
        $client = static::createClient();

        $date = $this->futureDate();
        $slot = $this->createAndPersistSlot($date);

        $client->request('POST', '/api/bookings', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'slotId' => $slot->getId(),
            'start' => $date.'T10:00:00+01:00',
            'end' => $date.'T10:30:00+01:00',
        ]));

        self::assertResponseStatusCodeSame(401);
    }

    public function testPastStartReturns422(): void
    {
        $client = static::createClient();
        $this->loginAsTestUser($client);

        $date = $this->futureDate();
        $slot = $this->createAndPersistSlot($date);

        $client->request('POST', '/api/bookings', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'slotId' => $slot->getId(),
            'start' => '2020-01-01T10:00:00+00:00',
            'end' => '2020-01-01T10:30:00+00:00',
        ]));

        self::assertResponseStatusCodeSame(422);
    }

    public function testViewingAnotherUsersBookingReturns403(): void
    {
        $client = static::createClient();

        $date = $this->futureDate();
        $owner = $this->createAndPersistUser();
        $slot = $this->createAndPersistSlot($date);
        $booking = $this->createConfirmedBooking($slot, $date.' 10:00', $date.' 11:00', $owner);

        $this->loginAsTestUser($client);

        $client->request('GET', '/api/bookings/'.$booking->getId());

        self::assertResponseStatusCodeSame(403);
    }
}
