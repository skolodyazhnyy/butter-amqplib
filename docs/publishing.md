# Publishing messages

## Construct message

In AMQP, message is a blob of data with properties. These properties are defined by AMQP protocol and can not be
extended. Although, for any kind of additional meta information you can use property `headers` which is an array of
key-value pairs.

Create an instance of `ButterAMQP\Message` and pass payload as first constructor argument, and properties as second. 

```php
use ButterAMQP\Message;

$payload = "text or binary data";

$properties = [
    'timestamp' => time(),
    'message-id' => uniqid('', true),
    'headers' => [
        'stream-id' => 1,
        'event' => 'record-updated',
    ],
];

$message = new Message($payload, $properties);
```

### Message Properties

| Properties       | Type   | Description                                                         |
|------------------|--------|---------------------------------------------------------------------|
| content-type     | string | Content type                                                        |     
| content-encoding | string | Content encoding                                                    |         
| headers          | array  | Additional headers                                                  |
| delivery-mode    | number | Delivery mode: 1 - non persistent, 2 - persistent                   |
| priority         | number | [Priority](https://www.rabbitmq.com/priority.html)                  |
| correlation-id   | string | Correlation ID for [RPC](rabbit-tutorial/tutorial-six.md) reply     |
| reply-to         | string | Reply queue name for [RPC](rabbit-tutorial/tutorial-six.md) request |
| expiration       | string | [Expiration time](https://www.rabbitmq.com/ttl.html)                |          
| message-id       | string | Message unique identifier                                           |
| timestamp        | long   | Unix timestamp                                                      |
| type             | string | Message type name                                                   |
| user-id          | string | Creating [user id](https://www.rabbitmq.com/validated-user-id.html) |
| app-id           | string | Creating application id                                             |

Property types are defined by protocol 

  - **string** - short string, up to 255 symbols
  - **array** - associated array
  - **number** - integer from -128 to 127
  - **long** - long long (int64) value

## Publish

When publishing a message you should specify which exchange and routing key should be used. Read more about [exchanges
and bindings](rabbit-tutorial/tutorial-three.md#exchanges) in RabbitMQ tutorial.
 
```php
$channel->publish($message, 'exchange-name', 'routing-key');
```
