<?php

namespace App\Controller\Api;

use App\Dto\CreateBookingRequest;
use App\Entity\User;
use App\Repository\SlotRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/bookings', methods: ['POST'])]
final class CreateBookingController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface  $validator,
        private readonly BookingService      $bookings,
        private readonly SlotRepository      $slots,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        $dto = $this->serializer->deserialize($request->getContent(), CreateBookingRequest::class, 'json');

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath().': '.$violation->getMessage();
            }

            return $this->json(['error' => 'validation_failed', 'violations' => $errors], 400);
        }

        $slot = $this->slots->find($dto->slotId)
            ?? throw new NotFoundHttpException('Slot not found.');

        try {
            $start = new \DateTimeImmutable($dto->start);
            $end = new \DateTimeImmutable($dto->end);
        } catch (\Exception) {
            return $this->json(['error' => 'validation_failed', 'message' => 'start/end must be valid ISO 8601 datetimes.'], 400);
        }

        $booking = $this->bookings->create($user, $slot, $start, $end);

        return $this->json($booking->toArray(), 201, [
            'Location' => $this->generateUrl('booking_show', ['id' => $booking->getId()]),
        ]);
    }
}
