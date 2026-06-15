<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalConfigurationUpdate
{
    public function __construct(
        public ?Configuration $planned = null,
        public ?Configuration $active = null,
    ) {
    }
}
