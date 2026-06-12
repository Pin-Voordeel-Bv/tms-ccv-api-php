<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalAttribute
{
    public function __construct(
        public ?string $name = null,
        public ?string $value = null,
    ) {
    }
}