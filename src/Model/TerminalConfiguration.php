<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalConfiguration
{
    public function __construct(
        public ?Configuration $active = null,
        public ?Configuration $planned = null,
    ) {
    }
}