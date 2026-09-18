<?php

namespace App\Controller\Api;

use App\Repository\SlotRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/slots', methods: ['GET'])]
final class ListAvailableSlotsController extends AbstractController
{
    public function __invoke(Request $request, SlotRepository $slots): JsonResponse
    {
        $date = $request->query->get('date');

        if (!is_string($date) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches) || !checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return $this->json(['error' => 'invalid_date', 'message' => 'date query parameter must be a valid YYYY-MM-DD date.'], 400);
        }

        $available = $slots->findAvailableForDate(new \DateTimeImmutable($date));

        return $this->json(array_map(static fn ($slot) => $slot->toArray(), $available));
    }
}
