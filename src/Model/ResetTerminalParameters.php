<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class ResetTerminalParameters
{
    /**
     * @param list<TerminalParameter>|null $parameters
     * @param list<ParameterValidationResult>|null $warnings
     */
    public function __construct(
        public ?string $tmsGateway = null,
        public ?string $terminalId = null,
        public ?string $jobId = null,
        public ?array $parameters = null,
        public ?array $warnings = null,
    ) {
    }
}
