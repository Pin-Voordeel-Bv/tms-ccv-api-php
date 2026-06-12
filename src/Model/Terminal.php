<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class Terminal
{
    public function __construct(
        public ?string $terminalId = null,
        public ?string $tmsGateway = null,
    ) {
    }
}