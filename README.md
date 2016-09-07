# Butter AMQP

_This is really work in progress AMQP library written purely in PHP, supporting only AMQP 0.9.1 (at least for the moment)._
 
This library provides functional level interfaces for interacting with AMQP server.

More documentation and complete support for AMQP 0.9.1 coming soon, but feel free to leave any suggestion or give feedback.

## Installation

Easiest way to start using Butter AMQP library is to install it using [composer](https://getcomposer.org/doc/00-intro.md#introduction). 
It has almost no dependencies and does not conflict with any other library.

Open a command console, enter your project directory and execute the following command to download the latest version of this library.

```bash
$ composer require skolodyazhnyy/butter-amqplib dev-master
```

This command requires you to have Composer installed globally, as explained in the  [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Usage

### Publishing messages

```php

use ButterAMQP\Connection;
use ButterAMQP\Message;

// Initialize connection to AMQP server
$connection = Connection::make("amqp://guest:guest@localhost:5672/");

// Connect to the server
$connection->open();

// Fetch a channel (thread within a connection)
$channel = $connection->channel();

// Construct a message to be published
$message = new Message('hi there', ['content-type' => 'plain/text']);

// Publish message to default exchange, with routing key "text-messages".
$channel->publish($message, '', 'text-message');

// Close connection
$connection->close();
```

### Consuming messages

```php

use ButterAMQP\Connection;
use ButterAMQP\Delivery;

// Initialize connection to AMQP server
$connection = Connection::make("amqp://guest:guest@localhost:5672/");

// Connect to the server
$connection->open();

// Fetch a channel (thread within a connection)
$channel = $connection->channel();

// Declare consumer
$consumer = $channel->consume('text-messages', function(Delivery $delivery) {
    echo "Receive a message: " . $delivery->getBody() . PHP_EOL;
    
    // Acknowledge delivery
    $delivery->ack();
});

// Serve connection until consumer is active
while($consumer->isActive()) {
    $connection->serve();
}

// Close connection
$connection->close();
```

## Known issues

- [ ] Decimal type is not supported
- [ ] Unsigned long long type is not supported
