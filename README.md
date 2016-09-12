# Butter AMQP

_This is really work in progress AMQP library written purely in PHP, supporting only AMQP 0.9.1 (at least for the moment)._
 
This library provides functional level interfaces for interacting with AMQP server.

More documentation coming soon, but feel free to leave any suggestion or give feedback.

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

Every code snippet below extends previous one.

### Connecting to the server

Establish connection to the server and open a channel. [Read more](/docs/connecting.md)

```php
use ButterAMQP\ConnectionManager;

$connection = ConnectionManager::make()
    ->connect("//guest:guest@localhost/%2f");

$channel = $connection->channel(1);
```

### Define topology

Declare exchanges and queues.

```php
use ButterAMQP\AMQP091\Exchange;
use ButterAMQP\AMQP091\Queue;

$channel->exchange('butter')
    ->define(Exchange::TYPE_FANOUT, Exchange::FLAG_DURABLE);
    
$channel->queue('butter')
    ->define(Queue::FLAG_DURABLE | Queue::FLAG_EXCLUSIVE)
    ->bind('butter');
```

### Publishing messages

Publish a message to newly declared exchange and it will be delivered to the queue.

```php
use ButterAMQP\Message;

// Construct a message to be published
$message = new Message('hi there', ['content-type' => 'text/plain']);

// Publish message to default exchange, with routing key "text-messages".
$channel->publish($message, '', 'text-messages');
```

### Consuming messages

Receive your message and acknowledge its delivery.

```php
use ButterAMQP\Delivery;

// Declare consumer
$consumer = $channel->consume('text-messages', function(Delivery $delivery) {
    echo "Receive a message: " . $delivery->getBody() . PHP_EOL;
    
    // Acknowledge delivery
    $delivery->ack();
});

// Serve connection until consumer is cancelled
while($consumer->isActive()) {
    $connection->serve();
}
```

### Close connection

Properly closing connection to the server will guarantee all temporary queues will be deleted and resources released.

You don't need to close channels, just connection will be enough.

```php
$connection->close();
```

## Known issues

- [ ] Decimal type is not supported
- [ ] Unsigned long long type is not supported
