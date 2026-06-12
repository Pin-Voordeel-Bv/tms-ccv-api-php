<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Client;

use GuzzleHttp\ClientInterface;
use PinVandaag\TmsCcvAPI\Exception\TmsCcvAPIException;
use PinVandaag\TmsCcvAPI\Model\Terminal;
use PinVandaag\TmsCcvAPI\Model\TerminalPaginationResult;
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
     * Retrieve a terminal by TMS gateway and terminal ID.
     *
     * @throws TmsCcvAPIException
     */
    public function getTerminal(
        string $tmsGateway,
        string $terminalId,
        ?string $onBehalfOf = null,
    ): Terminal {
        $tmsGateway = trim($tmsGateway);
        $terminalId = trim($terminalId);

        if ($tmsGateway === '') {
            throw new TmsCcvAPIException('CCV terminal request requires an tmsGateway.');
        }
        if ($terminalId === '') {
            throw new TmsCcvAPIException('CCV terminal request requires an terminalId.');
        }

        if ($onBehalfOf !== null && !preg_match('/^.{3,60}$/u', $onBehalfOf)) {
            throw new TmsCcvAPIException('X-On-Behalf-Of must be 3 to 60 characters when provided.');
        }

        /** @var Terminal $terminal */
        $terminal = $this->get(
            endpoint: sprintf('/external/terminals/%s/%s', rawurlencode($tmsGateway), rawurlencode($terminalId)),
            responseClass: Terminal::class,
            actionDescription: sprintf('retrieve CCV terminal "%s" on gateway "%s"', $terminalId, $tmsGateway),
            headers: $onBehalfOf === null ? [] : ['X-On-Behalf-Of' => $onBehalfOf],
        );

        return $terminal;
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

    private function assertNonEmptyString(string $value, string $fieldName): void
    {
        if ($value === '') {
            throw new TmsCcvAPIException(sprintf('CCV terminal request requires %s.', $fieldName));
       }
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
