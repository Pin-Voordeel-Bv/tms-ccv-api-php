<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalHardware
{
    public function __construct(
        public ?string $serialNumber = null,
        public ?string $model = null,
    ) {
    }
}