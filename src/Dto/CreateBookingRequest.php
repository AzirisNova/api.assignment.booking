<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateBookingRequest
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $slotId;

    #[Assert\NotBlank]
    public string $start;

    #[Assert\NotBlank]
    public string $end;
}
