Tutorial Three: Publish/Subscribe
=================================

> This article originally taken from [tutorial for RabbitMQ](https://www.rabbitmq.com/tutorials/tutorial-three-php.html)
  and adopted with examples for Butter AMQP PHP library.

In the [previous tutorial](tutorial-two.md) we created a work queue. The assumption behind a work queue is that each task is delivered to
exactly one worker. In this part we'll do something completely different - we'll deliver a message to multiple
consumers. This pattern is known as "publish/subscribe".

To illustrate the pattern, we're going to build a simple logging system. It will consist of two programs - the first
will emit log messages and the second will receive and print them.

In our logging system every running copy of the receiver program will get the messages. That way we'll be able to run
one receiver and direct the logs to disk; and at the same time we'll be able to run another receiver and see the logs on
the screen.

Essentially, published log messages are going to be broadcast to all the receivers.

## Exchanges

In previous parts of the tutorial we sent and received messages to and from a queue. Now it's time to introduce the full
messaging model in Rabbit.

Let's quickly go over what we covered in the previous tutorials:

- A **producer** is a user application that sends messages.
- A **queue** is a buffer that stores messages.
- A **consumer** is a user application that receives messages.

The core idea in the messaging model in RabbitMQ is that the producer never sends any messages directly to a queue.
Actually, quite often the producer doesn't even know if a message will be delivered to any queue at all.
 
Instead, the producer can only send messages to an exchange. An exchange is a very simple thing. On one side it receives
messages from producers and the other side it pushes them to queues. The exchange must know exactly what to do with a
message it receives. Should it be appended to a particular queue? Should it be appended to many queues? Or should it get
discarded. The rules for that are defined by the exchange type.

![P -> X -> Q1 ->> Q2](https://www.rabbitmq.com/img/tutorials/exchanges.png)

There are a few exchange types available: `direct`, `topic`, `headers` and `fanout`. We'll focus on the last one - the
fanout. Let's create an exchange of this type, and call it logs:

```php
$channel->exchange('logs')
    ->define('fanout');
```

The fanout exchange is very simple. As you can probably guess from the name, it just broadcasts all the messages it
receives to all the queues it knows. And that's exactly what we need for our logger.

#### Listing exchanges

To list the exchanges on the server you can run the ever useful `rabbitmqctl`:

```bash
sudo rabbitmqctl list_exchanges
Listing exchanges ...
        direct
amq.direct      direct
amq.fanout      fanout
amq.headers     headers
amq.match       headers
amq.rabbitmq.log        topic
amq.rabbitmq.trace      topic
amq.topic       topic
logs    fanout
...done.
```

In this list there are some amq.* exchanges and the default (unnamed) exchange. These are created by default, but it is
unlikely you'll need to use them at the moment.

#### Nameless exchange

In previous parts of the tutorial we knew nothing about exchanges, but still were able to send messages to queues. That
was possible because we were using a default exchange, which is identified by the empty string ("").

Recall how we published a message before:

```php
$channel->publish($message, '', 'hello');
```

Here we use the default or nameless exchange: messages are routed to the queue with the name specified by `routing_key`,
if it exists. The routing key is the third argument to publish.

Now, we can publish to our named exchange instead:

```php
$channel->exchange('logs')
    ->define('fanout');

$channel->publish($message, 'logs');
```

## Temporary queues

As you may remember previously we were using queues which had a specified name (remember `hello` and `task_queue`?).
Being able to name a queue was crucial for us -- we needed to point the workers to the same queue. Giving a queue a name
is important when you want to share the queue between producers and consumers.

But that's not the case for our logger. We want to hear about all log messages, not just a subset of them. We're also
interested only in currently flowing messages not in the old ones. To solve that we need two things.

Firstly, whenever we connect to Rabbit we need a fresh, empty queue. To do this we could create a queue with a random
name, or, even better - let the server choose a random queue name for us.

Secondly, once we disconnect the consumer the queue should be automatically deleted.

In the Butter AMQP library, when we supply queue name as an empty string, we create a non-durable queue with a generated
name:

```
$queue = $channel->queue()
    ->define(Queue::FLAG_EXCLUSIVE | Queue::FLAG_AUTO_DELETE);

$queueName = $queue->name();
```

When the method returns, the `$queueName` variable contains a random queue name generated by RabbitMQ. For example, it
may look like `amq.gen-JzTY20BRgKO-HjmUJj0wLg`.

> **Note** You don't need to call `Queue::name()` explicitly, you can work with `$queue` object. It provides API to get
  information about number of messages in the queue and number of consumers for the queue. Of course, for a temporary
  queue these always will be zeros, but for existent queue you may actually get some interesting numbers. If you try to
  use `Queue` object as a string it will be automatically converted into string containing queue name.

When the connection that declared it closes, the queue will be deleted because it is declared as exclusive.

## Bindings

![P -> X -> Q1 ->> Q2](https://www.rabbitmq.com/img/tutorials/bindings.png)

We've already created a fanout exchange and a queue. Now we need to tell the exchange to send messages to our queue.
That relationship between exchange and a queue is called a binding.

```php
$queue->bind('logs');
```

From now on the logs exchange will append messages to our queue.

## Putting it all together

![P -> X -> Q1 ->> Q2 -> C1 ->> C2](https://www.rabbitmq.com/img/tutorials/python-three-overall.png)

The producer program, which emits log messages, doesn't look much different from the previous tutorial. The most
important change is that we now want to publish messages to our `logs` exchange instead of the nameless one. Here goes
the code for `emit_log.php` script:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ButterAMQP\ConnectionBuilder;
use ButterAMQP\Message;
use ButterAMQP\AMQP091\Exchange;

$connection = ConnectionBuilder::make()
    ->create('//guest:guest@localhost');

$channel = $connection->channel();

$channel->exchange('logs')
    ->define(Exchange::TYPE_FANOUT);

$data = implode(' ', array_slice($argv, 1));

if (empty($data)) {
    $data = "info: Hello World!";
}
    
$message = new Message($data);

$channel->publish($message, 'logs');

echo " [x] Sent " . $data . PHP_EOL;

$channel->close();
$connection->close();
```

As you see, after establishing the connection we declared the exchange. This step is necessary as publishing to a
non-existing exchange is forbidden.

The messages will be lost if no queue is bound to the exchange yet, but that's okay for us; if no consumer is listening
yet we can safely discard the message.

The code for `receive_logs.php`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ButterAMQP\ConnectionBuilder;
use ButterAMQP\Delivery;
use ButterAMQP\AMQP091\Queue;
use ButterAMQP\AMQP091\Consumer;

$connection = ConnectionBuilder::make()
    ->create('//guest:guest@localhost');

$channel = $connection->channel();

$channel->exchange('logs')
    ->define('fanout');

$queue = $channel->queue()
    ->define(Queue::FLAG_EXCLUSIVE | Queue::FLAG_AUTO_DELETE);
    
$queue->bind('logs');

echo ' [*] Waiting for logs. To exit press CTRL+C' . PHP_EOL;

$callback = function(Delivery $delivery){
  echo ' [x] ' . $delivery->getBody() . PHP_EOL;
};

$consumer = $channel->consume($queue, $callback, Consumer::FLAG_NO_ACK);

while ($consumer->isActive()) {
    $connection->serve();
}

$channel->close();
$connection->close();
```

If you want to save logs to a file, just open a console and type:

```bash
$ php receive_logs.php > logs_from_rabbit.log
```

If you wish to see the logs on your screen, spawn a new terminal and run:

```bash
$ php receive_logs.php
```

And of course, to emit logs type:

```bash
php emit_log.php
```

Using `rabbitmqctl list_bindings` you can verify that the code actually creates bindings and queues as we want. With two
`receive_logs.php` programs running you should see something like:

```bash
$ sudo rabbitmqctl list_bindings
Listing bindings ...
logs    exchange        amq.gen-JzTY20BRgKO-HjmUJj0wLg  queue           []
logs    exchange        amq.gen-vso0PVvyiRIL2WoV3i48Yg  queue           []
...done.
```

The interpretation of the result is straightforward: data from exchange logs goes to two queues with server-assigned
names. And that's exactly what we intended.

To find out how to listen for a subset of messages, let's move on to [tutorial 4](tutorial-four.md)
