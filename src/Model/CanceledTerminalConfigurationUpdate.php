<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class CanceledTerminalConfigurationUpdate
{
    public function __construct(
        public ?string $tmsGateway = null,
        public ?string $terminalId = null,
        public ?string $jobId = null,
        public ?TerminalConfigurationCancel $configuration = null,
    ) {
    }
}
