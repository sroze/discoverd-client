# Discoverd PHP client

This PHP library is a PHP client for [Flynn Discoverd API](https://github.com/flynn/flynn/blob/master/discoverd/docs/API.md) which is based on a custom RPC server that supports JSON.

## Installation

Require the package `sroze/discoverd-client` in your `composer.json` file and update dependencies:

```json
{
    "require": {
        "sroze/discoverd-client": "~0.1.0"
    }
}
```

## Usage

*Note*: The client is able to automatically detect the Discoverd address based on the environment if your application is running on Flynn.

```php
<?php

use SRIO\Discoverd\Client;

// Create the Discoverd client
$client = new Client();

// Get informations about the service named "pg"
$serviceName = 'pg';
$result = $client->subscribe($serviceName);

// You can also call any method on RPC server
$client->call('Agent.Register', array(
    'Name' => 'anyservice',
    'Addr' => '1.2.3.4:5678'
));

```
