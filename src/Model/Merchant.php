<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class Merchant
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $erpSystem = null,
        public ?string $erpId = null,
        public ?string $contactInfo = null,
    ) {
    }
}
