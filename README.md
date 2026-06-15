## About
A PHP Wrapper for the <a href="https://tms-demo.ccvdev.eu/api/external/index.html">CCV - Estate Management - Terminal Management</a>

## Installation
`composer require pinvandaag/tms-ccv-api-php`

## Small usage example

```php
<?php

use Dotenv\Dotenv;
use PinVandaag\BuckarooAPI\TmsCcvAPIClient;

final class BuckarooController
{
    private TmsCcvAPIClient $apiClient;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../../../");
        $dotenv->safeLoad();
        $dotenv->required(['TMS_CCV_API_KEY'])->notEmpty();

        $this->apiClient = (new TmsCcvAPIClient())
            ->configure(
                apiKey: $_ENV['TMS_CCV_API_KEY'],
                baseUri: 'https://tms-demo-ccvdev.eu/api'
            );
    }

    public function getTerminal()
    {
        $tmsGateway = $_GET['tmsGateway'] ?? null;

        if ($tmsGateway === null || $tmsGateway === '') {
            throw new \RuntimeException('Missing tmsGateway.');
        }

        $terminalId = $_GET['terminalId'] ?? null;

        if ($terminalId === null || $terminalId === '') {
            throw new \RuntimeException('Missing terminalId.');
        }

        $result = $this->apiClient->getTerminal(
            tmsGateway: $tmsGateway,
            terminalId: $terminalId
        );

        $this->jsonResponse([
            'data' => $result,
        ]);
    }
}
```

## Example urls

```
    // http://example.com/ccv/hardware?tmsGatewayId=GrundmasterNL&serialNumber=0124000540008319
    // http://example.com/ccv/terminals/parameters?tmsGateway=GrundmasterNL&terminalId=123456789
    // http://example.com/ccv/terminals/parameters?tmsGateway=GrundmasterNL&terminalId=123456789&parameters=Applications/Ctap
    // http://example.com/ccv/terminals/parameters?tmsGateway=GrundmasterNL&terminalId=123456789&parameters[]=Applications/Ctap&parameters[]=SomeOtherParameter
    // http://example.com/ccv/terminals/parameters/update-deprecated?tmsGateway=GrundmasterNL&terminalId=123456789&id=Applications/Ctap&value=true&notes=test
    // http://example.com/ccv/terminals/parameters/patch-deprecated?tmsGateway=GrundmasterNL&terminalId=123456789&id=Applications/Ctap&value=true&notes=test
    // http://example.com/ccv/terminals/parameters/reset?tmsGateway=GrundmasterNL&terminalId=123456789
    // http://example.com/ccv/terminals/parameters/reset?tmsGateway=GrundmasterNL&terminalId=123456789&parameters=Applications/Ctap
    // http://example.com/ccv/terminals/parameters/reset?tmsGateway=GrundmasterNL&terminalId=123456789&parameters[]=Applications/Ctap&parameters[]=Ctap/Terminal%20ID%20CCVPay
    // http://example.com/ccv/get_terminals?externalIds=1234
    // http://example.com/ccv/terminals/create?terminalId=123456789&organizationId=60403d08-05e0-4af7-b112-e6c95d8dd668&tmsGateway=GrundmasterNL&softwareTree=SoftPOS&configurationName=SOFTPOS-MYPP&configurationVersion=1.0.1&locationId=2768ea81-6af2-48c2-9e7f-c1003d7e6a56&notes=test
    // http://example.com/ccv/terminals?tmsGateway=GrundmasterNL&terminalId=111301781
    // http://example.com/ccv/terminals/update?tmsGateway=GrundmasterNL&terminalId=123456789&name=ExternalId&value=POS-001
    // http://example.com/ccv/terminals/update?tmsGateway=GrundmasterNL&terminalId=123456789&name=ContractReference&value=ABC123
    // http://example.com/ccv/terminals/activation-status?tmsGateway=GrundmasterNL&terminalId=111301781
    // http://example.com/ccv/terminals/activation-status/set?tmsGateway=GrundmasterNL&terminalId=123456789&status=Activated
    // http://example.com/ccv/terminals/activation-status/set?tmsGateway=GrundmasterNL&terminalId=123456789&status=Deactivated
    // http://example.com/ccv/terminals/configuration/update-deprecated?tmsGateway=GrundmasterNL&terminalId=123456789&configurationName=SOFTPOS-MYPP&configurationVersion=1.0.1
    // http://example.com/ccv/terminals/configuration/update?tmsGateway=GrundmasterNL&terminalId=123456789&configurationName=SOFTPOS-MYPP
    // http://example.com/ccv/terminals/configuration/update?tmsGateway=GrundmasterNL&terminalId=123456789&configurationName=SOFTPOS-MYPP&configurationVersion=1.0.1
    // http://example.com/ccv/terminals/configuration/update/cancel?tmsGateway=GrundmasterNL&terminalId=123456789
    // http://example.com/ccv/get_version
    ```

## Example routes

```
    $router->mount("/ccv", function () use ($router) {
        $router->get('/hardware', 'TmsCcvController@getHardwareBySerialNumber');
        $router->get('/terminals/parameters', 'TmsCcvController@getTerminalParameters');
        $router->get('/terminals/parameters/update-deprecated', 'TmsCcvController@updateTerminalParametersDeprecated');
        $router->get('/terminals/parameters/patch-deprecated', 'TmsCcvController@patchTerminalParametersDeprecated');
        $router->get('/terminals/parameters/reset', 'TmsCcvController@resetTerminalParameters');
        $router->get("/get_terminals", "TmsCcvController@getTerminals");
        $router->get("/terminals/create", "TmsCcvController@createTerminal");
        $router->get("/terminals", "TmsCcvController@getTerminal");
        $router->get("/terminals/update", "TmsCcvController@updateTerminal");
        $router->get('/terminals/activation-status', 'TmsCcvController@getTerminalActivationStatus');
        $router->get('/terminals/activation-status/set', 'TmsCcvController@setTerminalActivationStatus');
        $router->get('/terminals/configuration/update-deprecated', 'TmsCcvController@updateTerminalConfigurationDeprecated');
        $router->get('/terminals/configuration/update', 'TmsCcvController@updateTerminalConfiguration');
        $router->get('/terminals/configuration/update/cancel', 'TmsCcvController@cancelTerminalConfigurationUpdate');
        $router->get("/get_version", "TmsCcvController@getVersion");
    });
```

## Example code

```
<?php

use Dotenv\Dotenv;
use PinVandaag\TmsCcvAPI\TmsCcvAPIClient;

final class TmsCcvController
{
    private TmsCcvAPIClient $apiClient;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../../../");
        $dotenv->safeLoad();
        $dotenv->required(['TMS_CCV_API_KEY'])->notEmpty();

        $this->apiClient = (new TmsCcvAPIClient())
            ->configure(
                apiKey: $_ENV['TMS_CCV_API_KEY'],
                baseUri: 'https://tms-demo.ccvdev.eu/api'
            );
    }

    public function getHardwareBySerialNumber()
    {
        $this->jsonResponse([
            'data' => $this->apiClient->getHardwareBySerialNumber(
                tmsGatewayId: (string) ($_GET['tmsGatewayId'] ?? ''),
                serialNumber: (string) ($_GET['serialNumber'] ?? ''),
                onBehalfOf: 'development@pinvandaag.com'
            ),
        ]);
    }

    public function getTerminalParameters()
    {
        $parameters = $_GET['parameters'] ?? [];

        if (is_string($parameters)) {
            $parameters = [$parameters];
        }

        $this->jsonResponse([
            'data' => $this->apiClient->getTerminalParameters(
                tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
                terminalId: (string) ($_GET['terminalId'] ?? ''),
                parameters: $parameters,
                onBehalfOf: 'development@pinvandaag.com'
            ),
        ]);
    }

    public function updateTerminalParametersDeprecated()
    {
        $parameters = [];

        if (isset($_GET['id'])) {
            $parameters[] = [
                'id' => (string) $_GET['id'],
                'value' => $_GET['value'] ?? null,
            ];
        }

        $this->jsonResponse([
            'data' => $this->apiClient->updateTerminalParametersDeprecated(
                tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
                terminalId: (string) ($_GET['terminalId'] ?? ''),
                parameters: $parameters,
                notes: $_GET['notes'] ?? null,
                onBehalfOf: 'development@pinvandaag.com'
            ),
        ]);
    }

    public function patchTerminalParametersDeprecated()
    {
        $parameters = [];

        if (isset($_GET['id'])) {
            $parameters[] = [
                'id' => (string) $_GET['id'],
                'value' => $_GET['value'] ?? null,
            ];
        }

        $this->jsonResponse([
            'data' => $this->apiClient->patchTerminalParametersDeprecated(
                tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
                terminalId: (string) ($_GET['terminalId'] ?? ''),
                parameters: $parameters,
                notes: $_GET['notes'] ?? null,
                onBehalfOf: 'development@pinvandaag.com'
            ),
        ]);
    }

    public function resetTerminalParameters()
    {
        $parameters = $_GET['parameters'] ?? [];

        if (is_string($parameters)) {
            $parameters = [$parameters];
        }

        $this->jsonResponse([
            'data' => $this->apiClient->resetTerminalParameters(
                tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
                terminalId: (string) ($_GET['terminalId'] ?? ''),
                parameters: $parameters,
                onBehalfOf: 'development@pinvandaag.com'
            ),
        ]);
    }

    public function getAllTerminals(
        array $externalIds = [],
        string $onBehalfOf = '',
        int $pageSize = 100
    ): array {
        $pageNumber = 1;
        $terminals = [];
        $totalPages = 1;

        do {
            $response = $this->apiClient->getTerminals(
                externalIds: $externalIds,
                pageNumber: $pageNumber,
                pageSize: $pageSize,
                onBehalfOf: $onBehalfOf
            );

            $terminals = array_merge(
                $terminals,
                $response->items ?? []
            );

            $totalPages = $response->totalPages ?? $totalPages;
            $pageNumber++;
        } while ($pageNumber <= $totalPages);

        return $terminals;
    }

    public function getTerminals()
    {
        $externalIds = $_GET['externalIds'] ?? [];

        if (!is_array($externalIds)) {
            $externalIds = [$externalIds];
        }

        $this->jsonResponse(
            $this->getAllTerminals(
                externalIds: $externalIds,
                pageSize: 250,
                onBehalfOf: 'development@pinvandaag.com'
            )
        );
    }

    public function createTerminal()
    {
        $result = $this->apiClient->createTerminal(
            payload: [
                'terminalId' => $_GET['terminalId'] ?? null,
                'organizationId' => $_GET['organizationId'] ?? null,
                'tmsGateway' => $_GET['tmsGateway'] ?? null,
                'softwareTree' => $_GET['softwareTree'] ?? null,
                'configurationName' => $_GET['configurationName'] ?? null,
                'configurationVersion' => $_GET['configurationVersion'] ?? null,
                'locationId' => $_GET['locationId'] ?? null,
                'notes' => $_GET['notes'] ?? null,
            ],
            onBehalfOf: 'development@pinvandaag.com'
        );

        $this->jsonResponse($result);
    }

    public function getTerminal()
    {
        $tmsGateway = $_GET['tmsGateway'] ?? null;

        if ($tmsGateway === null || $tmsGateway === '') {
            throw new \RuntimeException('Missing tmsGateway.');
        }

        $terminalId = $_GET['terminalId'] ?? null;

        if ($terminalId === null || $terminalId === '') {
            throw new \RuntimeException('Missing terminalId.');
        }

        $result = $this->apiClient->getTerminal(
            tmsGateway: $tmsGateway,
            terminalId: $terminalId,
            onBehalfOf: 'development@pinvandaag.com'
        );

        $this->jsonResponse([
            'data' => $result,
        ]);
    }

    public function updateTerminal()
    {
        $this->jsonResponse(
            $this->apiClient->updateTerminal(
                tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
                terminalId: (string) ($_GET['terminalId'] ?? ''),
                payload: [
                    'attributes' => [
                        [
                            'name' => (string) ($_GET['name'] ?? ''),
                            'value' => $_GET['value'] ?? null,
                        ],
                    ],
                ],
                onBehalfOf: 'development@pinvandaag.com'
            )
        );
    }

    public function getTerminalActivationStatus()
    {
        $result = $this->apiClient->getTerminalActivationStatus(
            tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
            terminalId: (string) ($_GET['terminalId'] ?? ''),
            onBehalfOf: 'development@pinvandaag.com'
        );

        $this->jsonResponse([
            'data' => $result,
        ]);
    }

    public function setTerminalActivationStatus()
    {
        $this->apiClient->setTerminalActivationStatus(
            tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
            terminalId: (string) ($_GET['terminalId'] ?? ''),
            status: (string) ($_GET['status'] ?? ''),
            onBehalfOf: 'development@pinvandaag.com'
        );

        $this->jsonResponse([
            'success' => true,
        ]);
    }

    public function updateTerminalConfigurationDeprecated()
    {
        $this->jsonResponse([
            'data' => $this->apiClient->updateTerminalConfigurationDeprecated(
                tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
                terminalId: (string) ($_GET['terminalId'] ?? ''),
                configurationName: (string) ($_GET['configurationName'] ?? ''),
                configurationVersion: $_GET['configurationVersion'] ?? null,
                onBehalfOf: 'development@pinvandaag.com'
            ),
        ]);
    }

    public function updateTerminalConfiguration()
    {
        $result = $this->apiClient->updateTerminalConfiguration(
            tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
            terminalId: (string) ($_GET['terminalId'] ?? ''),
            configurationName: (string) ($_GET['configurationName'] ?? ''),
            configurationVersion: $_GET['configurationVersion'] ?? null,
            onBehalfOf: 'development@pinvandaag.com'
        );

        $this->jsonResponse([
            'data' => $result,
        ]);
    }

    public function cancelTerminalConfigurationUpdate()
    {
        $result = $this->apiClient->cancelTerminalConfigurationUpdate(
            tmsGateway: (string) ($_GET['tmsGateway'] ?? ''),
            terminalId: (string) ($_GET['terminalId'] ?? ''),
            onBehalfOf: 'development@pinvandaag.com'
        );

        $this->jsonResponse([
            'data' => $result,
        ]);
    }

    public function getVersion()
    {
        $this->jsonResponse([
            'version' => $this->apiClient->getVersion(),
        ]);
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');

        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}
```