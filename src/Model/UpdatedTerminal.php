<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class UpdatedTerminal
{
    /**
     * @param list<TerminalAttribute>|null $attributes
     */
    public function __construct(
        public ?string $tmsGateway = null,
        public ?string $terminalId = null,
        public ?array $attributes = null,
    ) {
    }
}
