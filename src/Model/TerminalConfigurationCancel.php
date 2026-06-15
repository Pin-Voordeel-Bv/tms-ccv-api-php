<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalConfigurationCancel
{
    public function __construct(
        public ?Configuration $cancelled = null,
        public ?Configuration $active = null,
    ) {
    }
}
