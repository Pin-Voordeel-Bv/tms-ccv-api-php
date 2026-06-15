<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class Version
{
    public function __construct(
        public string $value,
    ) {
    }
}
