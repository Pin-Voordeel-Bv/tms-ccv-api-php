<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class HardwareBySerialNumberResponse
{
    /**
     * @param list<HardwareBySerialNumberItem> $items
     */
    public function __construct(
        public array $items = [],
    ) {
    }
}
