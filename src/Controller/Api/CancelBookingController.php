<?php

namespace App\Controller\Api;

use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/bookings/{id}', methods: ['DELETE'])]
final class CancelBookingController extends AbstractController
{
    public function __invoke(int $id, BookingRepository $bookings, EntityManagerInterface $em): JsonResponse
    {
        $booking = $bookings->find($id) ?? throw new NotFoundHttpException('Booking not found.');

        if ($booking->getUser() !== $this->getUser()) {
            throw new AccessDeniedHttpException('You may not cancel this booking.');
        }
        $booking->cancel();
        $em->flush();

        return new JsonResponse(null, 204);
    }
}
