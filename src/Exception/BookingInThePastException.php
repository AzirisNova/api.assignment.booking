<?php

namespace App\Exception;

final class BookingInThePastException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('A booking cannot start in the past.');
    }
}
