<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class Configuration
{
    public function __construct(
        public ?string $name = null,
        public ?string $version = null,
    ) {
    }
}
