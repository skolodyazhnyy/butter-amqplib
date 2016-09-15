# Consuming messages

## Define consumer

AMQP server delivers messages asynchronously, once a new message available server will send it over the wire to the
client, then Butter AMQP will decode message into an object and invoke a callable registered as consumer.
   
Define a consumer callable using `Channel::consume`. It takes name of the queue from which messages should be consumed
and callable.

Callable should take `ButterAMQP\Delivery` as an argument, this class extends `ButterAMQP\Message` with some additional
parameters and methods.
 
```php
use ButterAMQP\Delivery;

$consumer = $channel->consume('message', function(Delivery $d) {
    echo $d->getBody().PHP_EOL;
    
    $d->ack();
});
```

## Serve Connection

```php
while ($consumer->isActive()) {
    $connection->serve();
}
```
