## About
A PHP Wrapper for the <a href="https://tms-demo.ccvdev.eu/api/external/index.html">CCV - Estate Management - Terminal Management</a>

## Installation
`composer require pinvandaag/tms-ccv-api-php`

## Small usage example

```php
<?php

use Dotenv\Dotenv;
use PinVandaag\BuckarooAPI\BuckarooAPIClient;

final class BuckarooController
{
    private TmsCcvAPIClient $apiClient;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../../../");
        $dotenv->safeLoad();
        $dotenv->required(['TMS_CCV_API_KEY'])->notEmpty();

        $this->apiClient = (new BuckarooAPIClient())
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