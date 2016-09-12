# Examples

## Non-secure connection

> **Tip!** Examples show default values for URL, so change ones which are different and remove ones which are the same.

Using a string

```php
use ButterAMQP\ConnectionManager;

$connection = ConnectionManager::make()
    ->connect("//guest:guest@localhost/%2f");
```

Using an array

```php
use ButterAMQP\ConnectionManager;

$connection = ConnectionManager::make()
    ->connect([
        'host'  => 'localhost',
        'user'  => 'guest',
        'password'  => 'guest',
        'port'  => 5672,
        'vhost' => '/',
    ]);
```

## Connection with self-signed certificate

Using a string

```php
use ButterAMQP\ConnectionManager;

$connection = ConnectionManager::make()
    ->connect("amqps://guest:guest@localhost/%2f?certfile=cert.pem&verify=0&allow_self_signed=1");
```

Using an array

```php
use ButterAMQP\ConnectionManager;

$connection = ConnectionManager::make()
    ->connect([
        'schema' => 'amqps',
        'host'  => 'localhost',
        'user'  => 'guest',
        'password'  => 'guest',
        'port'  => 5672,
        'vhost' => '/',
        'parameters' => [
            'certfile' => 'cert.pem',
            'verify' => flase,
            'allow_self_signed' => true,
        ],
    ]);
```

## Secure connection

Using a string

```php
use ButterAMQP\ConnectionManager;

$connection = ConnectionManager::make()
    ->connect("amqps://guest:guest@localhost/%2f");
```

Using an array

```php
use ButterAMQP\ConnectionManager;

$connection = ConnectionManager::make()
    ->connect([
        'schema' => 'amqps',
        'host'  => 'localhost',
        'user'  => 'guest',
        'password'  => 'guest',
        'port'  => 5672,
        'vhost' => '/',
    ]);
```

