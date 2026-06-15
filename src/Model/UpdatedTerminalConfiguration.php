<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class UpdatedTerminalConfiguration
{
    public function __construct(
        public ?string $tmsGateway = null,
        public ?string $terminalId = null,
        public ?string $jobId = null,
        public ?TerminalConfigurationUpdate $configuration = null,
    ) {
    }
}
