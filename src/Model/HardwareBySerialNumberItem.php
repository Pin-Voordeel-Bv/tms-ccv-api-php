<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class HardwareBySerialNumberItem
{
    public function __construct(
        public string $serialNumber,
        public ?string $terminalId,
        public string $softwareTree,
        public string $tmsGatewayId,
        public ?string $terminalFamily,
        public ?string $hardwareModel,
        public string $businessPartnerId,
        public string $businessPartnerName,
        public ?string $notes,
    ) {
    }
}
