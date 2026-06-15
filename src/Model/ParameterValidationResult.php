<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class ParameterValidationResult
{
    public function __construct(
        public ?string $type = null,
        public ?string $parameterId = null,
        public ?string $message = null,
    ) {
    }
}
