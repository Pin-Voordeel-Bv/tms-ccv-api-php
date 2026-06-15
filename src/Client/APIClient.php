<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Client;

use GuzzleHttp\ClientInterface;
use PinVandaag\TmsCcvAPI\Exception\TmsCcvAPIException;
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
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use SensitiveParameter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

final class APIClient
{
    use LoggerAwareTrait;

    private readonly SerializerInterface $serializer;
    private ?string $apiKey = null;

    public function __construct(
        private readonly ClientInterface $client,
        private string $baseUri = '',
        ?SerializerInterface $serializer = null,
    ) {
        $this->baseUri = rtrim($this->baseUri, '/');
        $this->serializer = $serializer ?? new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()],
        );
    }

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = rtrim($baseUri, '/');

        return $this;
    }

    public function setApiKey(#[SensitiveParameter] string $apiKey): self
    {
        if ($apiKey === '') {
            throw new TmsCcvAPIException('CCV API key cannot be empty.');
        }

        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Retrieve the Terminal ID linked to a hardware serial number.
     *
     * @throws TmsCcvAPIException
     */
    public function getHardwareBySerialNumber(
        string $tmsGatewayId,
        string $serialNumber,
        ?string $onBehalfOf = null,
    ): HardwareBySerialNumberResponse {
        $tmsGatewayId = $this->assertStringField($tmsGatewayId, 'tmsGatewayId', required: true, maxLength: 50);
        $serialNumber = $this->assertStringField($serialNumber, 'serialNumber', required: true, maxLength: 50);
        $this->assertOnBehalfOf($onBehalfOf);

        /** @var HardwareBySerialNumberResponse $response */
        $response = $this->get(
            endpoint: sprintf(
                '/external/hardware/%s/%s',
                rawurlencode($tmsGatewayId),
                rawurlencode($serialNumber)
            ),
            responseClass: HardwareBySerialNumberResponse::class,
            actionDescription: sprintf(
                'retrieve CCV hardware "%s" on gateway "%s"',
                $serialNumber,
                $tmsGatewayId
            ),
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );

        return $response;
    }

    /**
     * Retrieve terminal parameters.
     *
     * Pass an empty parameter list to retrieve all available parameters.
     *
     * @param list<string> $parameters
     *
     * @throws TmsCcvAPIException
     */
    public function getTerminalParameters(
        string $tmsGateway,
        string $terminalId,
        array $parameters = [],
        ?string $onBehalfOf = null,
    ): TerminalParameters {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);

        foreach ($parameters as $index => $parameter) {
            $parameters[$index] = $this->assertStringField(
                $parameter,
                sprintf('parameters[%d]', $index),
                required: true
            );
        }

        $this->assertOnBehalfOf($onBehalfOf);

        /** @var TerminalParameters $terminalParameters */
        $terminalParameters = $this->post(
            endpoint: sprintf(
                '/external/terminals/%s/%s/parameters',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            payload: [
                'parameters' => array_values($parameters),
            ],
            responseClass: TerminalParameters::class,
            actionDescription: sprintf(
                'retrieve CCV terminal parameters for "%s" on gateway "%s"',
                $terminalId,
                $tmsGateway
            ),
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );

        return $terminalParameters;
    }

    /**
     * Update terminal parameters.
     *
     * @deprecated CCV deprecated this endpoint. Use PATCH /external/terminals/{tmsGateway}/{terminalId}/parameters once implemented.
     *
     * @param list<array{id:string,value:mixed}> $parameters
     *
     * @throws TmsCcvAPIException
     */
    public function updateTerminalParametersDeprecated(
        string $tmsGateway,
        string $terminalId,
        array $parameters,
        ?string $notes = null,
        ?string $onBehalfOf = null,
    ): ?UpdatedTerminalParameters {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);

        if ($parameters === []) {
            throw new TmsCcvAPIException('parameters is required and must contain at least one parameter.');
        }

        foreach ($parameters as $index => $parameter) {
            if (!is_array($parameter)) {
                throw new TmsCcvAPIException(sprintf('parameters[%d] must be an array.', $index));
            }

            $parameters[$index]['id'] = $this->assertStringField(
                $parameter['id'] ?? null,
                sprintf('parameters[%d].id', $index),
                required: true
            );

            $parameters[$index]['value'] = $parameter['value'] ?? null;
        }

        $notes = $this->assertStringField($notes, 'notes', required: false);
        $this->assertOnBehalfOf($onBehalfOf);

        $response = $this->request(
            method: 'PUT',
            endpoint: sprintf(
                '/external/terminals/%s/%s/parameters',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            options: [
                'headers' => $this->defaultHeaders() + ['Content-Type' => 'application/json'] + $this->optionalOnBehalfOfHeader($onBehalfOf),
                'json' => $this->filterPayload([
                    'parameters' => array_values($parameters),
                    'notes' => $notes,
                ]),
            ],
            actionDescription: sprintf(
                'update CCV terminal parameters for "%s" on gateway "%s"',
                $terminalId,
                $tmsGateway
            ),
        );

        if ($response->getStatusCode() === 201 || trim((string) $response->getBody()) === '') {
            return null;
        }

        /** @var UpdatedTerminalParameters $updatedParameters */
        $updatedParameters = $this->deserializeResponse(
            response: $response,
            responseClass: UpdatedTerminalParameters::class,
            actionDescription: 'deserialize updated CCV terminal parameters',
        );

        return $updatedParameters;
    }

    /**
     * Update terminal parameters.
     *
     * @deprecated CCV deprecated this endpoint. Use PATCH /api/terminals/{tmsGateway}/{terminalId}/parameters instead.
     *
     * @param list<array{id:string,value:mixed}> $parameters
     *
     * @throws TmsCcvAPIException
     */
    public function patchTerminalParametersDeprecated(
        string $tmsGateway,
        string $terminalId,
        array $parameters,
        ?string $notes = null,
        ?string $onBehalfOf = null,
    ): ?UpdatedTerminalParameters {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);

        if ($parameters === []) {
            throw new TmsCcvAPIException('parameters is required and must contain at least one parameter.');
        }

        foreach ($parameters as $index => $parameter) {
            if (!is_array($parameter)) {
                throw new TmsCcvAPIException(sprintf('parameters[%d] must be an array.', $index));
            }

            $parameters[$index]['id'] = $this->assertStringField(
                $parameter['id'] ?? null,
                sprintf('parameters[%d].id', $index),
                required: true
            );

            $parameters[$index]['value'] = $parameter['value'] ?? null;
        }

        $notes = $this->assertStringField($notes, 'notes', required: false);
        $this->assertOnBehalfOf($onBehalfOf);

        /** @var UpdatedTerminalParameters $updatedParameters */
        $updatedParameters = $this->patch(
            endpoint: sprintf(
                '/external/terminals/%s/%s/parameters',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            payload: [
                'parameters' => array_values($parameters),
                'notes' => $notes,
            ],
            responseClass: UpdatedTerminalParameters::class,
            actionDescription: sprintf(
                'patch CCV terminal parameters for "%s" on gateway "%s"',
                $terminalId,
                $tmsGateway
            ),
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );

        return $updatedParameters;
    }

    /**
     * Reset terminal parameters to their default values.
     *
     * Pass an empty parameter list to reset all parameters.
     *
     * @param list<string> $parameters
     *
     * @throws TmsCcvAPIException
     */
    public function resetTerminalParameters(
        string $tmsGateway,
        string $terminalId,
        array $parameters = [],
        ?string $onBehalfOf = null,
    ): ResetTerminalParameters {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);

        foreach ($parameters as $index => $parameter) {
            $parameters[$index] = $this->assertStringField(
                $parameter,
                sprintf('parameters[%d]', $index),
                required: true
            );
        }

        $this->assertOnBehalfOf($onBehalfOf);

        /** @var ResetTerminalParameters $resetParameters */
        $resetParameters = $this->patch(
            endpoint: sprintf(
                '/external/terminals/%s/%s/parameters/reset',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            payload: [
                'parameters' => array_values($parameters),
            ],
            responseClass: ResetTerminalParameters::class,
            actionDescription: sprintf(
                'reset CCV terminal parameters for "%s" on gateway "%s"',
                $terminalId,
                $tmsGateway
            ),
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );

        return $resetParameters;
    }

     /**
     * Retrieve a paginated list of terminals.
     *
     * @param list<string> $externalIds Optional filter on External IDs.
     *
     * @throws TmsCcvAPIException
     */
    public function getTerminals(
        array $externalIds = [],
        int $pageNumber = 1,
        int $pageSize = 50,
        ?string $onBehalfOf = null,
    ): TerminalPaginationResult {
        $this->assertExternalIds($externalIds);
        $this->assertPositiveInteger($pageNumber, 'PageNumber');
        $this->assertPositiveInteger($pageSize, 'PageSize');
        $this->assertOnBehalfOf($onBehalfOf);

        $query = [
            'PageNumber' => $pageNumber,
            'PageSize' => $pageSize,
        ];

        if ($externalIds !== []) {
            $query['ExternalIds'] = $externalIds;
        }

        /** @var TerminalPaginationResult $terminals */
        $terminals = $this->get(
            endpoint: '/external/terminals',
            responseClass: TerminalPaginationResult::class,
            actionDescription: 'get CCV terminals',
            query: $query,
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );

        return $terminals;
    }

    /**
     * Create a terminal.
     *
     * @param array<string, mixed> $payload
     *
     * @throws TmsCcvAPIException
     */
    public function createTerminal(
        array $payload,
        ?string $onBehalfOf = null,
    ): CreatedTerminal {
        $this->assertCreateTerminalPayload($payload);
        $this->assertOnBehalfOf($onBehalfOf);

        /** @var CreatedTerminal $terminal */
        $terminal = $this->post(
            endpoint: '/external/terminals',
            payload: $payload,
            responseClass: CreatedTerminal::class,
            actionDescription: 'create CCV terminal',
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );

        return $terminal;
    }

    /**
     * Retrieve a terminal by TMS gateway and terminal ID.
     *
     * @throws TmsCcvAPIException
     */
    public function getTerminal(
        string $tmsGateway,
        string $terminalId,
        ?string $onBehalfOf = null,
    ): Terminal {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);
        $this->assertOnBehalfOf($onBehalfOf);

        /** @var Terminal $terminal */
        $terminal = $this->get(
            endpoint: sprintf('/external/terminals/%s/%s', rawurlencode($tmsGateway), rawurlencode($terminalId)),
            responseClass: Terminal::class,
            actionDescription: sprintf('retrieve CCV terminal "%s" on gateway "%s"', $terminalId, $tmsGateway),
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );

        return $terminal;
    }

    /**
     * Update a terminal.
     *
     * @param array<string, mixed> $payload
     *
     * @throws TmsCcvAPIException
     */
    public function updateTerminal(
        string $tmsGateway,
        string $terminalId,
        array $payload,
        ?string $onBehalfOf = null,
    ): UpdatedTerminal {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);

        $this->assertUpdateTerminalPayload($payload);
        $this->assertOnBehalfOf($onBehalfOf);

        /** @var UpdatedTerminal $terminal */
        $terminal = $this->patch(
            endpoint: sprintf('/external/terminals/%s/%s', rawurlencode($tmsGateway), rawurlencode($terminalId)),
            payload: $payload,
            responseClass: UpdatedTerminal::class,
            actionDescription: sprintf('update CCV terminal "%s" on gateway "%s"', $terminalId, $tmsGateway),
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );

        return $terminal;
    }

    /**
     * Get the activation status of a terminal.
     *
     * Returns null when CCV responds with 204 No Content.
     *
     * @throws TmsCcvAPIException
     */
    public function getTerminalActivationStatus(
        string $tmsGateway,
        string $terminalId,
        ?string $onBehalfOf = null,
    ): ?TerminalActivationStatus {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);
        $this->assertOnBehalfOf($onBehalfOf);

        $response = $this->request(
            method: 'GET',
            endpoint: sprintf(
                '/external/terminals/%s/%s/activation-status',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            options: [
                'headers' => $this->defaultHeaders() + $this->optionalOnBehalfOfHeader($onBehalfOf),
            ],
            actionDescription: sprintf(
                'retrieve CCV terminal activation status for "%s" on gateway "%s"',
                $terminalId,
                $tmsGateway
            ),
        );

        if ($response->getStatusCode() === 204) {
            return null;
        }

        /** @var TerminalActivationStatus $activationStatus */
        $activationStatus = $this->deserializeResponse(
            response: $response,
            responseClass: TerminalActivationStatus::class,
            actionDescription: 'deserialize CCV terminal activation status',
        );

        return $activationStatus;
    }

    /**
     * Set the activation status of a terminal.
     *
     * Only "Activated" and "Deactivated" are accepted for setting.
     *
     * @throws TmsCcvAPIException
     */
    public function setTerminalActivationStatus(
        string $tmsGateway,
        string $terminalId,
        string $status,
        ?string $onBehalfOf = null,
    ): void {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);
        $status = $this->assertStringField($status, 'status', required: true);

        if (!in_array($status, ['Unknown', 'Activated', 'Deactivated'], true)) {
            throw new TmsCcvAPIException('status must be one of:  Unknown, Activated, Deactivated.');
        }

        $this->assertOnBehalfOf($onBehalfOf);

        $this->put(
            endpoint: sprintf(
                '/external/terminals/%s/%s/activation-status',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            payload: [
                'status' => $status,
            ],
            actionDescription: sprintf(
                'set CCV terminal activation status to "%s" for "%s" on gateway "%s"',
                $status,
                $terminalId,
                $tmsGateway
            ),
            headers: $this->optionalOnBehalfOfHeader($onBehalfOf),
        );
    }

    /**
     * Update terminal configuration.
     *
     * @deprecated CCV deprecated this endpoint. Use updateTerminalConfiguration() instead.
     *
     * @throws TmsCcvAPIException
     */
    public function updateTerminalConfigurationDeprecated(
        string $tmsGateway,
        string $terminalId,
        string $configurationName,
        ?string $configurationVersion = null,
        ?string $onBehalfOf = null,
    ): ?UpdatedTerminalConfiguration {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);
        $configurationName = $this->assertStringField(
            $configurationName,
            'configuration.name',
            required: true,
            maxLength: 191
        );
        $configurationVersion = $this->assertStringField(
            $configurationVersion,
            'configuration.version',
            required: false,
            maxLength: 191
        );

        $this->assertOnBehalfOf($onBehalfOf);

        $response = $this->request(
            method: 'PUT',
            endpoint: sprintf(
                '/external/terminals/%s/%s/configuration',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            options: [
                'headers' => $this->defaultHeaders() + ['Content-Type' => 'application/json'] + $this->optionalOnBehalfOfHeader($onBehalfOf),
                'json' => $this->filterPayload([
                    'configuration' => [
                        'name' => $configurationName,
                        'version' => $configurationVersion,
                    ],
                ]),
            ],
            actionDescription: sprintf(
                'update CCV terminal configuration for "%s" on gateway "%s" using deprecated endpoint',
                $terminalId,
                $tmsGateway
            ),
        );

        if ($response->getStatusCode() === 201 || trim((string) $response->getBody()) === '') {
            return null;
        }

        /** @var UpdatedTerminalConfiguration $updatedConfiguration */
        $updatedConfiguration = $this->deserializeResponse(
            response: $response,
            responseClass: UpdatedTerminalConfiguration::class,
            actionDescription: 'deserialize deprecated CCV terminal configuration update',
        );

        return $updatedConfiguration;
    }

    /**
     * Initiate a configuration update for a terminal.
     *
     * Only GrundmasterNL and GrundmasterBE are supported.
     *
     * Returns null when CCV responds with 201 Created without a response body.
     *
     * @throws TmsCcvAPIException
     */
    public function updateTerminalConfiguration(
        string $tmsGateway,
        string $terminalId,
        string $configurationName,
        ?string $configurationVersion = null,
        ?string $onBehalfOf = null,
    ): ?UpdatedTerminalConfiguration {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);
        $configurationName = $this->assertStringField(
            $configurationName,
            'configuration.name',
            required: true,
            maxLength: 191
        );
        $configurationVersion = $this->assertStringField(
            $configurationVersion,
            'configuration.version',
            required: false,
            maxLength: 191
        );

        if (!in_array($tmsGateway, ['GrundmasterNL', 'GrundmasterBE'], true)) {
            throw new TmsCcvAPIException('tmsGateway must be one of: GrundmasterNL, GrundmasterBE for configuration updates.');
        }

        $this->assertOnBehalfOf($onBehalfOf);

        $response = $this->request(
            method: 'PUT',
            endpoint: sprintf(
                '/external/terminals/%s/%s/configuration/update',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            options: [
                'headers' => $this->defaultHeaders() + ['Content-Type' => 'application/json'] + $this->optionalOnBehalfOfHeader($onBehalfOf),
                'json' => $this->filterPayload([
                    'configuration' => [
                        'name' => $configurationName,
                        'version' => $configurationVersion,
                    ],
                ]),
            ],
            actionDescription: sprintf(
                'update CCV terminal configuration for "%s" on gateway "%s"',
                $terminalId,
                $tmsGateway
            ),
        );

        if ($response->getStatusCode() === 201 || trim((string) $response->getBody()) === '') {
            return null;
        }

        /** @var UpdatedTerminalConfiguration $updatedConfiguration */
        $updatedConfiguration = $this->deserializeResponse(
            response: $response,
            responseClass: UpdatedTerminalConfiguration::class,
            actionDescription: 'deserialize CCV terminal configuration update',
        );

        return $updatedConfiguration;
    }

    /**
     * Cancel a pending configuration update for a terminal.
     *
     * Only GrundmasterNL and GrundmasterBE are supported.
     *
     * Returns null when CCV responds with 201 Created without a response body.
     *
     * @throws TmsCcvAPIException
     */
    public function cancelTerminalConfigurationUpdate(
        string $tmsGateway,
        string $terminalId,
        ?string $onBehalfOf = null,
    ): ?CanceledTerminalConfigurationUpdate {
        $tmsGateway = $this->assertStringField($tmsGateway, 'tmsGateway', required: true, maxLength: 50);
        $terminalId = $this->assertStringField($terminalId, 'terminalId', required: true, maxLength: 50);

        if (!in_array($tmsGateway, ['GrundmasterNL', 'GrundmasterBE'], true)) {
            throw new TmsCcvAPIException('tmsGateway must be one of: GrundmasterNL, GrundmasterBE for configuration update cancellation.');
        }

        $this->assertOnBehalfOf($onBehalfOf);

        $response = $this->request(
            method: 'DELETE',
            endpoint: sprintf(
                '/external/terminals/%s/%s/configuration/update',
                rawurlencode($tmsGateway),
                rawurlencode($terminalId)
            ),
            options: [
                'headers' => $this->defaultHeaders() + $this->optionalOnBehalfOfHeader($onBehalfOf),
            ],
            actionDescription: sprintf(
                'cancel CCV terminal configuration update for "%s" on gateway "%s"',
                $terminalId,
                $tmsGateway
            ),
        );

        if ($response->getStatusCode() === 201 || trim((string) $response->getBody()) === '') {
            return null;
        }

        /** @var CanceledTerminalConfigurationUpdate $canceledConfigurationUpdate */
        $canceledConfigurationUpdate = $this->deserializeResponse(
            response: $response,
            responseClass: CanceledTerminalConfigurationUpdate::class,
            actionDescription: 'deserialize canceled CCV terminal configuration update',
        );

        return $canceledConfigurationUpdate;
    }

    /**
     * @throws TmsCcvAPIException
     */
    public function getVersion(): string
    {
        $response = $this->request(
            method: 'GET',
            endpoint: '/external/version',
            options: [
                'headers' => $this->defaultHeaders(),
            ],
            actionDescription: 'retrieve CCV API version',
        );

        $body = (string) $response->getBody();

        $decoded = json_decode($body, true);

        if (!is_string($decoded)) {
            throw new TmsCcvAPIException(
                sprintf(
                    'Unexpected response while retrieving CCV API version. Response body: %s',
                    $body
                )
            );
        }

        return $decoded;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     * @param array<string, string> $headers
     *
     * @return T
     *
     * @throws TmsCcvAPIException
     */
    private function get(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
        array $headers = [],
    ): object {
        $response = $this->request(
            method: 'GET',
            endpoint: $endpoint,
            options: [
                'headers' => $this->defaultHeaders() + $headers,
                'query' => $this->filterPayload($query),
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResponse($response, $responseClass, $actionDescription);
    }

    /**
     * @template T of object
     *
     * @param array<string, mixed> $payload
     * @param class-string<T> $responseClass
     * @param array<string, string> $headers
     *
     * @return T
     *
     * @throws TmsCcvAPIException
     */
    private function post(
        string $endpoint,
        array $payload,
        string $responseClass,
        string $actionDescription,
        array $headers = [],
    ): object {
        $response = $this->request(
            method: 'POST',
            endpoint: $endpoint,
            options: [
                'headers' => $this->defaultHeaders() + ['Content-Type' => 'application/json'] + $headers,
                'json' => $this->filterPayload($payload),
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResponse($response, $responseClass, $actionDescription);
    }

    /**
     * @template T of object
     *
     * @param array<string, mixed> $payload
     * @param class-string<T> $responseClass
     * @param array<string, string> $headers
     *
     * @return T
     *
     * @throws TmsCcvAPIException
     */
    private function patch(
        string $endpoint,
        array $payload,
        string $responseClass,
        string $actionDescription,
        array $headers = [],
    ): object {
        $response = $this->request(
            method: 'PATCH',
            endpoint: $endpoint,
            options: [
                'headers' => $this->defaultHeaders() + ['Content-Type' => 'application/json'] + $headers,
                'json' => $this->filterPayload($payload),
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResponse($response, $responseClass, $actionDescription);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     *
     * @throws TmsCcvAPIException
     */
    private function put(
        string $endpoint,
        array $payload,
        string $actionDescription,
        array $headers = [],
    ): void {
        $this->request(
            method: 'PUT',
            endpoint: $endpoint,
            options: [
                'headers' => $this->defaultHeaders() + ['Content-Type' => 'application/json'] + $headers,
                'json' => $this->filterPayload($payload),
            ],
            actionDescription: $actionDescription,
        );
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws TmsCcvAPIException
     */
    private function request(
        string $method,
        string $endpoint,
        array $options,
        string $actionDescription,
    ): ResponseInterface {
        try {
            return $this->client->request(
                $method,
                $this->uri($endpoint),
                $options + [
                    'connect_timeout' => 8.0,
                    'http_errors' => false,
                    'timeout' => 25.0,
                    'verify' => true,
                ],
            );
        } catch (Throwable $exception) {
            throw new TmsCcvAPIException(sprintf('Could not %s.', $actionDescription), 0, $exception);
        }
    }

    /**
     * @return array<string, string>
     */
    private function defaultHeaders(): array
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new TmsCcvAPIException('CCV API key has not been configured.');
        }

        return [
            'Accept' => 'application/json',
            'X-Api-Key' => $this->apiKey,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function filterPayload(array $payload): array
    {
        return array_filter(
            $payload,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     *
     * @return T
     *
     * @throws TmsCcvAPIException
     */
    private function deserializeResponse(
        ResponseInterface $response,
        string $responseClass,
        string $actionDescription,
    ): object {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new TmsCcvAPIException(
                $this->errorMessageFromResponseBody($body, $actionDescription, $statusCode),
                $statusCode,
            );
        }

        try {
            /** @var T $result */
            $result = $this->serializer->deserialize($body, $responseClass, 'json');
        } catch (SerializerException $exception) {
            throw new TmsCcvAPIException(
                sprintf('Could not deserialize CCV response for %s.', $actionDescription),
                0,
                $exception
            );
        }

        return $result;
    }

    private function errorMessageFromResponseBody(
        string $body,
        string $actionDescription,
        int $statusCode,
    ): string {
        $trimmedBody = trim($body);

        if ($trimmedBody === '') {
            return sprintf(
                'CCV request failed while trying to %s with HTTP %d.',
                $actionDescription,
                $statusCode,
            );
        }

        $decoded = json_decode($trimmedBody, true);

        if (is_array($decoded)) {
            if (isset($decoded['detail']) && is_string($decoded['detail']) && $decoded['detail'] !== '') {
                $message = $decoded['detail'];

                if (isset($decoded['title']) && is_string($decoded['title']) && $decoded['title'] !== '') {
                    $message = $decoded['title'] . ': ' . $message;
                }

                return $message;
            }

            if (isset($decoded['title']) && is_string($decoded['title']) && $decoded['title'] !== '') {
                return $decoded['title'];
            }

            if (isset($decoded['message']) && is_string($decoded['message']) && $decoded['message'] !== '') {
                return $decoded['message'];
            }
        }

        return $trimmedBody;
    }

    private function uri(string $endpoint): string
    {
        return $this->baseUri . '/' . ltrim($endpoint, '/');
    }

    /** @return array<string, string> */
    private function optionalOnBehalfOfHeader(?string $onBehalfOf): array
    {
        if ($onBehalfOf === null || $onBehalfOf === '') {
           return [];
        }

        return ['X-On-Behalf-Of' => $onBehalfOf];
    }

    private function assertStringField(
        mixed $value,
        string $fieldName,
        bool $required = false,
        ?int $minLength = null,
        ?int $maxLength = null,
    ): ?string {
        if ($value === null || $value === '') {
            if ($required) {
                throw new TmsCcvAPIException(sprintf('%s is required and must be a non-empty string.', $fieldName));
            }

            return null;
        }

        if (!is_string($value)) {
            throw new TmsCcvAPIException(sprintf('%s must be a string.', $fieldName));
        }

        $value = trim($value);

        if ($value === '') {
            if ($required) {
                throw new TmsCcvAPIException(sprintf('%s is required and must be a non-empty string.', $fieldName));
            }

            return null;
        }

        if ($minLength !== null && strlen($value) < $minLength) {
            throw new TmsCcvAPIException(sprintf('%s must be at least %d characters.', $fieldName, $minLength));
        }

        if ($maxLength !== null && strlen($value) > $maxLength) {
            throw new TmsCcvAPIException(sprintf('%s may not be longer than %d characters.', $fieldName, $maxLength));
        }

        return $value;
    }

    private function assertPositiveInteger(int $value, string $fieldName): void
    {
        if ($value < 1) {
            throw new TmsCcvAPIException(sprintf('%s must be at least 1.', $fieldName));
        }
    }

    /** @param list<string> $externalIds */
    private function assertExternalIds(array $externalIds): void
    {
        foreach ($externalIds as $externalId) {
            if (!is_string($externalId) || $externalId === '') {
                throw new TmsCcvAPIException('ExternalIds must only contain non-empty strings.');
            }
        }
    }

    /** @param array<string, mixed> $payload */
    private function assertCreateTerminalPayload(array $payload): void
    {
        $this->assertRequiredStringField($payload, 'organizationId');
        $this->assertRequiredStringField($payload, 'tmsGateway', 50);
        $this->assertRequiredStringField($payload, 'softwareTree', 50);
        $this->assertRequiredStringField($payload, 'configurationName');

        $this->assertOptionalStringField($payload, 'terminalId', 50);
        $this->assertOptionalStringField($payload, 'configurationVersion');
        $this->assertOptionalStringField($payload, 'locationId');
        $this->assertOptionalStringField($payload, 'notes', 250);
    }

    /** @param array<string, mixed> $payload */
    private function assertUpdateTerminalPayload(array $payload): void
    {
        if (!isset($payload['attributes']) || !is_array($payload['attributes']) || $payload['attributes'] === []) {
            throw new TmsCcvAPIException('attributes is required and must contain at least one attribute.');
        }

        foreach ($payload['attributes'] as $index => $attribute) {
            if (!is_array($attribute)) {
                throw new TmsCcvAPIException(sprintf('attributes[%d] must be an array.', $index));
            }

            $this->assertStringField(
                $attribute['name'] ?? null,
                sprintf('attributes[%d].name', $index),
                required: true,
            );

            if (!array_key_exists('value', $attribute) || $attribute['value'] === null) {
                continue;
            }

            $this->assertStringField(
                $attribute['value'],
                sprintf('attributes[%d].value', $index),
                required: true,
                minLength: 1,
                maxLength: 64,
            );
        }
    }

    /** @param array<string, mixed> $payload */
    private function assertRequiredStringField(
        array $payload,
        string $fieldName,
        ?int $maxLength = null,
    ): void {
        $this->assertStringField(
            $payload[$fieldName] ?? null,
            $fieldName,
            required: true,
            maxLength: $maxLength,
        );
    }

    /** @param array<string, mixed> $payload */
    private function assertOptionalStringField(
        array $payload,
        string $fieldName,
        ?int $maxLength = null,
    ): void {
        $this->assertStringField(
            $payload[$fieldName] ?? null,
            $fieldName,
            required: false,
            maxLength: $maxLength,
        );
    }

    private function assertOnBehalfOf(?string $onBehalfOf): void
    {
        if ($onBehalfOf === null || $onBehalfOf === '') {
            return;
        }

        $length = strlen($onBehalfOf);

        if ($length < 3 || $length > 60) {
            throw new TmsCcvAPIException('X-On-Behalf-Of must be between 3 and 60 characters.');
        }
    }
}
