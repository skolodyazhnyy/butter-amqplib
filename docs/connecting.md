# Connecting to the server

Before sending and receiving messages you would need to establish connection to the AMQP server.
It includes defining a URL, normally by reading some configuration file, instantiating Connection object and opening a channel.  

Below you will find detailed description of how to establish connection to the server, and which options exists.
But if you happen to need a quick code snippet, I have prepared some:

- [Non-secure connection](examples.md#non-secure-connection)
- [Connection with self-signed certificate](exmaples.md#connection-with-self-signed-certificate)
- []Secure connection](examples.md#secure-connection)

## 1. Build a URL

In Butter AMQP library, URL represents server protocol, address, credentials and all other parameters required to establish connection.
It can be defined as instance of `ButterAMQP\Url` class, a string or an array. 

**String URL format** is conforming to [RabbitMQ URI specification](https://www.rabbitmq.com/uri-spec.html) with some [additional parameters](#url-parameters).
This format can be used in your configuration files as simple and compact way to configure AMQP connection. Just make sure **all special symbols are URL encoded**.

```php
$url = "amqp://guest:guest@localhost/%2f?connection_timeout=1&timeout=0.5";
```

**Array URL format** is an array with several optional keys which represent different parts of the URL.
Array elements don't need to be encoded. This format can be used in your configuration files as most readable. 

```php
$url = [
    'scheme' => 'amqp',
    'host' => 'localhost',
    'port' => 5672,
    'user' => 'guest',
    'password' => 'guest',
    'vhost' => '/',
    'parameters' => [
        'connection_timeout' => 1,
        'timeout' => 0.5,
    ],
];
```

All available top level keys are listed above.

### URL Parameters (for all URL formats)
 
 
| Parameter          | PHP SSL Context   | description                                                                                               |
|--------------------|-------------------|-----------------------------------------------------------------------------------------------------------|
| connection_timeout | -                 | Connection timeout in seconds                                                                             |
| timeout            | -                 | Reading timeout, all blocking calls will return control once this timeout reached                         |
| read_ahead         | -                 | Size of "read ahead" buffer. Use 0 to disable reading ahead                                               |
| certfile           | local_cert        | Path to locally stored SSL certificate (private key + certificate)                                        |
| keyfile            | local_pk          | Path to locally stored Private Key                                                                        |
| cacertfile         | cafile            | Path to locally stored CA certificate                                                                     |
| passphrase         | passphrase        | Passpharase for private key                                                                               |
| verify             | verify_peer       | Boolean flag, should SSL connection verify certificate, normally `true` but `false` can be used for tests |
| allow_self_signed  | allow_self_signed | Boolean flag, should SSL connection allow self signed certificates, useful for development environments   |

> When it comes to parameter names **Butter AMQP** tries to stick to AMQP URI specification, rather than PHP specific names. 
  But you can still find [PHP names](http://php.net/manual/en/context.ssl.php#refsect1-context.ssl-options) in "PHP SSL context" column for reference.

## 2. Create connection

Once URL is built, use `ButterAMQP\ConnectionBuilder` to create a connection.
 
```
use ButterAMQP\ConnectionBuilder;

$connection = ConnectionBuilder::make()->connect($url);
```

## 3. Open a channel

AMQP protocol defines channels, independent stream of frames within a TCP connection. These allow multiple threads in your
application to communicate with server independently. While one thread may wait for a synchronous response form the server,
another still can receive an asynchronous frame and process it without interrupting others, because they will use different
channels.

There are very few operations that can be performed on the connection itself, but most of them are performed within a channel.

Taking in account, PHP applications in most cases are single threaded you won't need more than one channel.
You can boldly open a channel in the beginning of your code and use it everywhere. Just keep in mind few things:
 
  - Opening a channel require open connection, so once you call `Connection::channel` it will establish TCP connection to the server. 
  - If connection fails by some reason you need to re-open channel again
  - Every time when you call `Connection::channel` without arguments it will open a new channel, if you want to always receive same channel you need to pass channel ID as an argument. For example `1`. 

And after all this boring theory, here is one, very practical line.

```php
$channel = $connection->channel(1);
```

Here you go, now you can start publising messages!