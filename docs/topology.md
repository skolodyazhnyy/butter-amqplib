# Topology

## Declare an exchange

```php
$channel->exchange('butter')
    ->define(Exchange::FLAG_DURABLE);
```

## Declare a queue

```php
$channel->queue('butter')
    ->define(Queue::FLAG_DURABLE | Queue::FLAG_EXCLUSIVE)
    ->bind('butter');
```

## Exchange to exchange binding

```php
$channel->exchange('butter')
    ->define(Exchange::FLAG_DURABLE)
    ->bind('other-exchange');
```
