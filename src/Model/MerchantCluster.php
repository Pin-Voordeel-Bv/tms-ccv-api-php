<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class MerchantCluster
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
    ) {
    }
}
