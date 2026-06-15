<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalActivationStatus
{
    public function __construct(
        public ?string $status = null,
    ) {
    }
}
