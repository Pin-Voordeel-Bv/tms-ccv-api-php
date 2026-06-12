<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI;

use GuzzleHttp\Client;
use PinVandaag\TmsCcvAPI\Client\APIClient;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

final class TmsCcvAPIClient
{
    private APIClient $apiClient;
    private ?string $apiKey = null;

    public function __construct(
        ?APIClient $apiClient = null,
        ?LoggerInterface $logger = null,
        ?string $baseUri = null,
    ) {
        $this->apiClient = $apiClient ?? new APIClient(new Client(), $baseUri ?? '');

        if ($logger !== null) {
            $this->apiClient->setLogger($logger);
        }
    }

    public function configure(
        #[SensitiveParameter] string $apiKey,
        ?string $baseUri = null,
    ): self {
        $this->apiKey = $apiKey;

        if ($baseUri !== null) {
            $this->apiClient->setBaseUri($baseUri);
        }

        return $this;
    }
    /**
     * Get application by id.
     */
    public function getTerminal(
        string $tmsGateway,
        string $terminalId,
    ): Application {
        return $this->apiClient->getTerminal($tmsGateway, $terminalId);
    }
}
