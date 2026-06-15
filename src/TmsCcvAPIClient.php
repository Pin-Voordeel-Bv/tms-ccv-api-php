<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI;

use GuzzleHttp\Client;
use PinVandaag\TmsCcvAPI\Client\APIClient;
use PinVandaag\TmsCcvAPI\Model\CanceledTerminalConfigurationUpdate;
use PinVandaag\TmsCcvAPI\Model\CreatedTerminal;
use PinVandaag\TmsCcvAPI\Model\HardwareBySerialNumberResponse;
use PinVandaag\TmsCcvAPI\Model\ResetTerminalParameters;
use PinVandaag\TmsCcvAPI\Model\Terminal;
use PinVandaag\TmsCcvAPI\Model\TerminalActivationStatus;
use PinVandaag\TmsCcvAPI\Model\TerminalPaginationResult;
use PinVandaag\TmsCcvAPI\Model\TerminalParameters;
use PinVandaag\TmsCcvAPI\Model\UpdatedTerminal;
use PinVandaag\TmsCcvAPI\Model\UpdatedTerminalConfiguration;
use PinVandaag\TmsCcvAPI\Model\UpdatedTerminalParameters;
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
     * Retrieve terminal ID(s) linked to a hardware serial number.
     */
    public function getHardwareBySerialNumber(
        string $tmsGatewayId,
        string $serialNumber,
        ?string $onBehalfOf = null,
    ): HardwareBySerialNumberResponse {
        return $this->apiClient->getHardwareBySerialNumber($tmsGatewayId, $serialNumber, $onBehalfOf);
    }

    /**
     * Retrieve terminal parameters.
     *
     * @param list<string> $parameters
     */
    public function getTerminalParameters(
        string $tmsGateway,
        string $terminalId,
        array $parameters = [],
        ?string $onBehalfOf = null,
    ): TerminalParameters {
        return $this->apiClient->getTerminalParameters($tmsGateway, $terminalId, $parameters, $onBehalfOf);
    }

    /**
     * @deprecated CCV deprecated this endpoint. Use patchTerminalParameters() once implemented.
     *
     * @param list<array{id:string,value:mixed}> $parameters
     */
    public function updateTerminalParametersDeprecated(
        string $tmsGateway,
        string $terminalId,
        array $parameters,
        ?string $notes = null,
        ?string $onBehalfOf = null,
    ): ?UpdatedTerminalParameters {
        return $this->apiClient->updateTerminalParametersDeprecated(
            $tmsGateway,
            $terminalId,
            $parameters,
            $notes,
            $onBehalfOf
        );
    }

    /**
     * @deprecated CCV deprecated this endpoint. Use the non-deprecated parameter update endpoint once available.
     *
     * @param list<array{id:string,value:mixed}> $parameters
     */
    public function patchTerminalParametersDeprecated(
        string $tmsGateway,
        string $terminalId,
        array $parameters,
        ?string $notes = null,
        ?string $onBehalfOf = null,
    ): ?UpdatedTerminalParameters {
        return $this->apiClient->patchTerminalParametersDeprecated(
            $tmsGateway,
            $terminalId,
            $parameters,
            $notes,
            $onBehalfOf
        );
    }

    /**
     * Reset terminal parameters to their default values.
     *
     * Pass an empty parameter list to reset all parameters.
     *
     * @param list<string> $parameters
     */
    public function resetTerminalParameters(
        string $tmsGateway,
        string $terminalId,
        array $parameters = [],
        ?string $onBehalfOf = null,
    ): ResetTerminalParameters {
        return $this->apiClient->resetTerminalParameters($tmsGateway, $terminalId, $parameters, $onBehalfOf);
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
     * Create a terminal.
     *
     * @param array<string, mixed> $payload
     */
    public function createTerminal(
        array $payload,
        ?string $onBehalfOf = null,
    ): CreatedTerminal {
        return $this->apiClient->createTerminal($payload, $onBehalfOf);
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

    /**
     * Update a terminal.
     *
     * @param array<string, mixed> $payload
     */
    public function updateTerminal(
        string $tmsGateway,
        string $terminalId,
        array $payload,
        ?string $onBehalfOf = null,
    ): UpdatedTerminal {
        return $this->apiClient->updateTerminal($tmsGateway, $terminalId, $payload, $onBehalfOf);
    }

    /**
     * Retrieve terminal activation status.
     */
    public function getTerminalActivationStatus(
        string $tmsGateway,
        string $terminalId,
        ?string $onBehalfOf = null,
    ): ?TerminalActivationStatus {
        return $this->apiClient->getTerminalActivationStatus($tmsGateway, $terminalId, $onBehalfOf);
    }

    /**
     * Set terminal activation status.
     */
    public function setTerminalActivationStatus(
        string $tmsGateway,
        string $terminalId,
        string $status,
        ?string $onBehalfOf = null,
    ): void {
        $this->apiClient->setTerminalActivationStatus($tmsGateway, $terminalId, $status, $onBehalfOf);
    }

    /**
     * @deprecated CCV deprecated this endpoint. Use updateTerminalConfiguration() instead.
     */
    public function updateTerminalConfigurationDeprecated(
        string $tmsGateway,
        string $terminalId,
        string $configurationName,
        ?string $configurationVersion = null,
        ?string $onBehalfOf = null,
    ): ?UpdatedTerminalConfiguration {
        return $this->apiClient->updateTerminalConfigurationDeprecated(
            $tmsGateway,
            $terminalId,
            $configurationName,
            $configurationVersion,
            $onBehalfOf
        );
    }

    /**
     * Initiate a terminal configuration update.
     */
    public function updateTerminalConfiguration(
        string $tmsGateway,
        string $terminalId,
        string $configurationName,
        ?string $configurationVersion = null,
        ?string $onBehalfOf = null,
    ): ?UpdatedTerminalConfiguration {
        return $this->apiClient->updateTerminalConfiguration(
            $tmsGateway,
            $terminalId,
            $configurationName,
            $configurationVersion,
            $onBehalfOf
        );
    }

    /**
     * Cancel a pending terminal configuration update.
     */
    public function cancelTerminalConfigurationUpdate(
        string $tmsGateway,
        string $terminalId,
        ?string $onBehalfOf = null,
    ): ?CanceledTerminalConfigurationUpdate {
        return $this->apiClient->cancelTerminalConfigurationUpdate($tmsGateway, $terminalId, $onBehalfOf);
    }

    public function getVersion(): string
    {
        return $this->apiClient->getVersion();
    }
}
