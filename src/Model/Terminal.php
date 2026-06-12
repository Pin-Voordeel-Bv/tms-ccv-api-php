<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class Terminal
{
    /**
     * @param list<TerminalAttribute>|null $attributes
     */
    public function __construct(
        public ?string $terminalId = null,
        public ?string $tmsGateway = null,
        public ?string $softwareVersion = null,
        public ?TerminalHardware $hardware = null,
        public ?TerminalConfiguration $configuration = null,
        public ?array $attributes = null,
    ) {
    }
}
