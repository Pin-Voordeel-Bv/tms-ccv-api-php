<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class UpdateTerminalParametersErrorResponse
{
    /**
     * @param list<ParameterValidationResult>|null $errors
     */
    public function __construct(
        public ?array $errors = null,
        public ?string $jobId = null,
    ) {
    }
}
