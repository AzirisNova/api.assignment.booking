<?php

namespace App\Exception;

final class DurationExceededException extends \RuntimeException
{
    public function __construct(int $maxDurationMinutes)
    {
        parent::__construct(sprintf('Booking exceeds the maximum permitted duration of %d minutes.', $maxDurationMinutes));
    }
}
