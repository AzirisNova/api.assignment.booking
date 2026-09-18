<?php

namespace App\Controller\Api;

use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/bookings/{id}', name: 'booking_show', methods: ['GET'])]
final class ShowBookingController extends AbstractController
{
    public function __invoke(int $id, BookingRepository $bookings): JsonResponse
    {
        $booking = $bookings->find($id) ?? throw new NotFoundHttpException('Booking not found.');

        if ($booking->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('You may not view this booking.');
        }

        return $this->json($booking->toArray());
    }
}
