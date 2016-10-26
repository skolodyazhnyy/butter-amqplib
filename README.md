# Butter AMQP

[![Build Status](https://travis-ci.org/skolodyazhnyy/butter-amqplib.svg?branch=master)](https://travis-ci.org/skolodyazhnyy/butter-amqplib)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/skolodyazhnyy/butter-amqplib/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/skolodyazhnyy/butter-amqplib/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/skolodyazhnyy/butter-amqplib/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/skolodyazhnyy/butter-amqplib/?branch=master)

Butter AMQP is a client library for AMQP protocol purely implemented in PHP. It has no dependencies on any PHP extension
nor other PHP packages. It's very light-weight and lightning fast.

Butter AMQP supports all base AMQP features and RabbitMQ extensions, including: [exchange to exchange bindings](https://www.rabbitmq.com/e2e.html),
[publisher acknowledgments](https://www.rabbitmq.com/confirms.html), [negative acknowledgements](https://www.rabbitmq.com/nack.html) and others.  

## Key features

- Pure PHP implementation of AMQP protocol: no special requirements for PHP and easy upgrade using just composer
- Easy to use functional API, it hides implementation details and reduce risk of making mistake
- Code generator for frame encoding and decoding helps achieve high performance and low memory usage
- Clean design makes it pleasure to work with AMQP, easy to tests and understand
- Full support for AMQP protocol version 0.9.1 and RabbitMQ extensions

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

If you are new to AMQP, I suggest you have a look into [RabbitMQ tutorial](docs/rabbit-tutorial/tutorial-one.md) which
explains all features of AMQP protocol in only 6 chapters tutorial! I have adopted it with code samples for Butter AMQP.

Every code snippet below extends previous one.

### Connecting to the server

Establish connection to the server and open a channel.

```php
use ButterAMQP\ConnectionBuilder;

$connection = ConnectionBuilder::make()
    ->create("//guest:guest@localhost/%2f");

$channel = $connection->channel(1);
```

[Read more](/docs/connecting.md)

### Define topology

Declare exchanges and queues.

```php
use ButterAMQP\ExchangeInterface as Exchange;
use ButterAMQP\QueueInterface as Queue;

$channel->exchange('butter')
    ->define(Exchange::TYPE_FANOUT, Exchange::FLAG_DURABLE);
    
$channel->queue('butter')
    ->define(Queue::FLAG_DURABLE | Queue::FLAG_EXCLUSIVE)
    ->bind('butter');
```

[Read more](/docs/topology.md)

### Publishing messages

Publish a message to newly declared exchange and it will be delivered to the queue.

```php
use ButterAMQP\Message;

// Construct a message to be published
$message = new Message('hi there', ['content-type' => 'text/plain']);

// Publish message to default exchange, with routing key "text-messages".
$channel->publish($message, '', 'text-messages');
```

[Read more](/docs/publishing.md)

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

[Read more](/docs/consuming.md)

### Close connection

Properly closing connection to the server will guarantee all temporary queues will be deleted and resources released.

You don't need to close channels, just connection will be enough.

```php
$connection->close();
```

## Known issues

- [ ] Decimal type is not supported
- [ ] Unsigned long long type is not supported
