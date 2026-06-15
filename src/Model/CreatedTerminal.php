<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class CreatedTerminal
{
    /**
     * @param array<string, string>|null $terminalProperties
     */
    public function __construct(
        public ?string $id = null,
        public ?string $terminalId = null,
        public ?string $tmsGateway = null,
        public ?string $softwareTree = null,
        public ?Organization $organization = null,
        public ?Configuration $configuration = null,
        public ?MerchantCluster $cluster = null,
        public ?Merchant $merchant = null,
        public ?Location $location = null,
        public ?array $terminalProperties = null,
    ) {
    }
}
