<?php

namespace App\EventListener;

use App\Exception\DurationExceededException;
use App\Exception\BookingInThePastException;
use App\Exception\InvalidTimeRangeException;
use App\Exception\OverlappingBookingException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionListener
{
    private const MAP = [
        OverlappingBookingException::class => 409,
        BookingInThePastException::class => 422,
        InvalidTimeRangeException::class => 422,
        DurationExceededException::class => 422,
    ];

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        foreach (self::MAP as $class => $status) {
            if ($exception instanceof $class) {
                $event->setResponse(new JsonResponse(['error' => $exception->getMessage()], $status));
                return;
            }
        }
    }
}
