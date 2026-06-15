<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class Location
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $address = null,
    ) {
    }
}
