<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalParameters
{
    /**
     * @param list<TerminalParameter>|null $parameters
     */
    public function __construct(
        public ?string $tmsGateway = null,
        public ?string $terminalId = null,
        public ?array $parameters = null,
    ) {
    }
}
