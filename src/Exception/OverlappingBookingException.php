<?php

namespace App\Exception;

final class OverlappingBookingException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('The requested time overlaps an existing confirmed booking.', previous: $previous);
    }
}
