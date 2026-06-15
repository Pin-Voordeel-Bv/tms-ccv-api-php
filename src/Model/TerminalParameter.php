<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalParameter
{
    public function __construct(
        public string $id,
        public mixed $value = null,
    ) {
    }
}
