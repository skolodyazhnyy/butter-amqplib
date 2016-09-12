Tutorial One: "Hello World"
===========================

> This article originally taken from [tutorial for RabbitMQ](https://www.rabbitmq.com/tutorials/tutorial-one-php.html)
  and adopted with examples for Butter AMQP PHP library.

RabbitMQ is a message broker. The principal idea is pretty simple: it accepts and forwards messages. You can think about
it as a post office: when you send mail to the post box you're pretty sure that Mr. Postman will eventually deliver the 
mail to your recipient. Using this metaphor RabbitMQ is a post box, a post office and a postman.

The major difference between RabbitMQ and the post office is the fact that it doesn't deal with paper, instead it
accepts, stores and forwards binary blobs of data ‒ messages.

RabbitMQ, and messaging in general, uses some jargon.

**Producing** means nothing more than sending. A program that sends messages is a producer. We'll draw it like that,
with "P":

![P](https://www.rabbitmq.com/img/tutorials/producer.png)

A **queue** is the name for a mailbox. It lives inside RabbitMQ. Although messages flow through RabbitMQ and your
applications, they can be stored only inside a queue. A queue is not bound by any limits, it can store as many messages
as you like ‒ it's essentially an infinite buffer. Many producers can send messages that go to one queue, many consumers
can try to receive data from one queue. A queue will be drawn as like that, with its name above it:

![Q, queue-name](https://www.rabbitmq.com/img/tutorials/queue.png)

**Consuming** has a similar meaning to receiving. A consumer is a program that mostly waits to receive messages. On our
drawings it's shown with "C":

![C](https://www.rabbitmq.com/img/tutorials/consumer.png)

Note that the producer, consumer, and broker do not have to reside on the same machine; indeed in most applications they
don't.

## "Hello World"

In this part of the tutorial we'll write two programs in PHP; a producer that sends a single message, and a consumer
that receives messages and prints them out. We'll gloss over some of the detail in the Butter AMQP API, concentrating on
this very simple thing just to get started. It's a "Hello World" of messaging.

In the diagram below, "P" is our producer and "C" is our consumer. The box in the middle is a queue - a message buffer
that RabbitMQ keeps on behalf of the consumer.

![P -> Q -> C](https://www.rabbitmq.com/img/tutorials/python-one.png)

RabbitMQ speaks multiple protocols. This tutorial covers **AMQP 0-9-1**, which is an open, general-purpose protocol for
messaging. There are a number of clients for RabbitMQ in many different languages. We'll use the Butter AMQP library in
this tutorial, and [Composer](https://getcomposer.org/doc/00-intro.md) for dependency management.

Add a `composer.json` file to your project, or follow instructions in the [installation guide](https://github.com/skolodyazhnyy/butter-amqplib#installation):

```json
{
    "require": {
        "skolodyazhnyy/butter-amqplib": "dev-master"
    }
}
```

Provided you have [Composer installed](https://getcomposer.org/doc/00-intro.md) and functional, you can run the following:

```bash
composer install
```

Now we have the Butter AMQP library installed, we can write some code:

## Sending

![P -> Q, hello](https://www.rabbitmq.com/img/tutorials/sending.png)

We'll call our message sender `send.php` and our message receiver `receive.php`. The sender will connect to RabbitMQ,
send a single message, then exit.

In `send.php`, we need to include the library and `use` the necessary classes:

```php
require_once __DIR__ . '/vendor/autoload.php';

use ButterAMQP\ConnectionBuilder;
use ButterAMQP\Message;
```

then we can create a connection to the server:

```php
$connection = ConnectionBuilder::make()->create('//guest:guest@localhost');
$channel = $connection->channel();
```

Read more about URL format and connection options in the [documentation for Butter AMQP](../connecting.md).

The connection abstracts the socket connection, and takes care of protocol version negotiation and authentication and so
on for us. Here we connect to a broker on the local machine - hence the `localhost`. If we wanted to connect to a broker
on a different machine we'd simply specify its name or IP address here.

Next we create a channel, which is where most of the API for getting things done resides.

To send, we must declare a queue for us to send to; then we can publish a message to the queue:

```php
$channel->queue('hello')
    ->define();

$message = new Message('Hello World!');
$channel->publish($message, '', 'hello');

echo " [x] Sent 'Hello World!'\n";
```

Declaring a queue is idempotent - it will only be created if it doesn't exist already. The message content is a byte
array, so you can encode whatever you like there.

Lastly, we close the channel and the connection:

```php
$channel->close();
$connection->close();
```

> **Sending doesn't work!** If this is your first time using RabbitMQ and you don't see the "Sent" message then you may
  be left scratching your head wondering what could be wrong. Maybe the broker was started without enough free disk
  space (by default it needs at least 1Gb free) and is therefore refusing to accept messages. Check the broker logfile
  to confirm and reduce the limit if necessary. The
  [configuration file documentation](http://www.rabbitmq.com/configure.html#config-items) will show you how to set
  disk_free_limit.

## Receiving

That's it for our sender. Our receiver is pushed messages from RabbitMQ, so unlike the sender which publishes a single
message, we'll keep it running to listen for messages and print them out.

![Q, hello -> C](https://www.rabbitmq.com/img/tutorials/receiving.png)

The code (in `receive.php`) has almost the same `require` and `uses` as in `send.php`:

```php
require_once __DIR__ . '/vendor/autoload.php';

use ButterAMQP\ConnectionBuilder;
use ButterAMQP\Delivery;
use ButterAMQP\AMQP091\Consumer;

$connection = ConnectionBuilder::make()->create('//guest:guest@localhost');
$channel = $connection->channel();

$channel->queue('hello')
    ->define();

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
```

Note that we declare the queue here, as well. Because we might start the receiver before the sender, we want to make
sure the queue exists before we try to consume messages from it.

We're about to tell the server to deliver us the messages from the queue. We will define a PHP callable that will
receive the messages sent by the server. Keep in mind that messages are sent asynchronously from the server to the
clients.

```php
$callback = function(Delivery $delivery) {
    echo " [x] Received " . $delivery->getBody() . PHP_EOL;
};

$consumer = $channel->consume('hello', $callback, Consumer::FLAG_NO_ACK);

while($consumer->isActive()) {
    $connection->serve();
}
```

Our code will block while our `$consumer` is active. Whenever we receive a message our `$callback` function will be
passed the received message.

You may notice that publisher publishes a Message, while consumer receives a Delivery. But really Delivery is just a
message with some additional data, like what exchange message was published to, what routing key was used, is it
delivered first time, or it was rejected and re-queued. Also you may notice there are few methods available for
delivery, like `Delivery::ack()` and `Delivery::reject()`. You will learn how to use these in the next tutorial.

## Putting it all together

Now we can run both scripts. In a terminal, run the sender:

```php
$ php send.php
```

then, run the receiver:

```php
$ php receive.php
```

The receiver will print the message it gets from the sender via RabbitMQ. The receiver will keep running, waiting for
messages (Use Ctrl-C to stop it), so try running the sender from another terminal.

If you want to check on the queue, try using `rabbitmqctl list_queues`.

Hello World!

Time to move on to [part 2](tutorial-two.md) and build a simple *work queue*.
