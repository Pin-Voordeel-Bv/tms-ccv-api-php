<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI;

use GuzzleHttp\Client;
use PinVandaag\TmsCcvAPI\Client\APIClient;
use PinVandaag\TmsCcvAPI\Model\Terminal;
use PinVandaag\TmsCcvAPI\Model\TerminalPaginationResult;
use Psr\Log\LoggerInterface;
use SensitiveParameter;

final class TmsCcvAPIClient
{
    private APIClient $apiClient;

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
        $this->apiClient->setApiKey($apiKey);

        if ($baseUri !== null) {
            $this->apiClient->setBaseUri($baseUri);
        }

        return $this;
    }

    /**
     * Retrieve a paginated list of terminals.
     *
     * @param list<string> $externalIds Optional filter on External IDs.
     */
    public function getTerminals(
        array $externalIds = [],
        int $pageNumber = 1,
        int $pageSize = 50,
        ?string $onBehalfOf = null,
    ): TerminalPaginationResult {
        return $this->apiClient->getTerminals($externalIds, $pageNumber, $pageSize, $onBehalfOf);
    }

    /**
     * Retrieve a terminal by TMS gateway and terminal ID.
     */
    public function getTerminal(
        string $tmsGateway,
        string $terminalId,
        ?string $onBehalfOf = null,
    ): Terminal  {
        return $this->apiClient->getTerminal($tmsGateway, $terminalId, $onBehalfOf);
    }
}
