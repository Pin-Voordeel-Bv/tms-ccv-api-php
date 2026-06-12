<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PinVandaag\TmsCcvAPI\Exception\TmsCcvAPIException;
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

    public function __construct(
        private readonly ClientInterface $client,
        private string $baseUri = '',
        ?SerializerInterface $serializer = null,
    ) {
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

    /**
     * Get an account by id.
     *
     * @throws BuckarooAPIException
     */
    public function getTerminal(
        string $tmsGateway,
        string $terminalId
    ): Account {
        if ($tmsGateway === '') {
            throw new BuckarooAPIException('CCV terminal request requires an tmsGateway.');
        }
        if ($terminalId === '') {
            throw new BuckarooAPIException('CCV terminal request requires an terminalId.');
        }

        /** @var Terminal $terminal */
        $terminal = $this->get(
            endpoint: sprintf('/external/terminals/%s/%s', rawurlencode($terminalId), rawurlencode($tmsGateway)),
            responseClass: Terminal::class,
            actionDescription: sprintf('get CCV terminal "%s"', $terminalId),
        );

        return $terminal;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     *
     * @return T
     *
     * @throws BuckarooAPIException
     */
    private function get(
        string $endpoint,
        string $responseClass,
        string $actionDescription,
        array $query = [],
    ): object {
        $query = $this->filterPayload($query);

        $response = $this->requestHal(
            method: 'GET',
            endpoint: $endpoint,
            options: [
                'headers' => [
                    'X-ApiKey' => $this->apiKey,
                ],
                'query' => $query,
            ],
            actionDescription: $actionDescription,
        );

        return $this->deserializeResponse($response, $responseClass, $actionDescription);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws BuckarooAPIException
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
     * @template T of object
     *
     * @param class-string<T> $responseClass
     *
     * @return T
     *
     * @throws BuckarooAPIException
     */
    private function deserializeResponse(
        ResponseInterface $response,
        string $responseClass,
        string $actionDescription,
    ): object {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new BuckarooAPIException(
                $this->errorMessageFromResponseBody($body, $actionDescription, $statusCode),
                $statusCode,
            );
        }

        try {
            /** @var T $result */
            $result = $this->serializer->deserialize($body, $responseClass, 'json');
        } catch (SerializerException $exception) {
            throw new BuckarooAPIException(
                sprintf('Could not deserialize Buckaroo response for %s.', $actionDescription),
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
                $statusCode
            );
        }

        $decoded = json_decode($trimmedBody, true);

        if (is_array($decoded)) {
            if (isset($decoded['detail']) && is_string($decoded['detail']) && $decoded['detail'] !== '') {
                $message = $decoded['detail'];

                if (isset($decoded['title']) && is_string($decoded['title']) && $decoded['title'] !== '') {
                    $message = $decoded['title'] . ': ' . $message;
                }

                if (isset($decoded['errorCode']) && $decoded['errorCode'] !== null && $decoded['errorCode'] !== '') {
                    $message .= ' Error code: ' . (string) $decoded['errorCode'] . '.';
                }

                return $message;
            }

            if (isset($decoded['title']) && is_string($decoded['title']) && $decoded['title'] !== '') {
                $message = $decoded['title'];

                if (isset($decoded['errorCode']) && $decoded['errorCode'] !== null && $decoded['errorCode'] !== '') {
                    $message .= '. Error code: ' . (string) $decoded['errorCode'] . '.';
                }

                return $message;
            }

            if (isset($decoded['message']) && is_string($decoded['message']) && $decoded['message'] !== '') {
                return $decoded['message'];
            }
        }

        return $trimmedBody;
    }
}