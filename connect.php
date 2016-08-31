<?php

use AMQPLib\Connection;
use AMQPLib\InputOutput\SocketInputOutput;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

require __DIR__.'/vendor/autoload.php';

$stream = new StreamHandler('php://stderr', LogLevel::DEBUG);
$stream->setFormatter(new LineFormatter(
    "[%datetime%] \033[32m\033[1m%channel%.%level_name%:\033[22m %message%\033[39m %context%\n",
    "H:i:s.u"
));

$io = new SocketInputOutput();
$io->setLogger(new Logger('io', [$stream]));

$conn = new AMQPLib\Connection('//localhost', $io);
$conn->setLogger(new Logger('connection', [$stream]));
$conn->open();

$ch = $conn->channel();
$ch->qos(0, 1, true);
$ch->exchange('rabbit')
    ->define('direct');

$ch->queue('rabbit')
    ->define(\AMQPLib\Queue::FLAG_AUTO_DELETE)
    ->bind('rabbit');

$ch->queue('rabbit')
    ->consume(function(\AMQPLib\Delivery $delivery) {
        echo $delivery->getBody() . PHP_EOL;
        $delivery->ack();
    });

$ch->exchange('rabbit')
    ->publish(new \AMQPLib\Message(str_repeat('x', 10), [
        'content-type' => 'plain/text',
        'content-encoding' => 'UTF-8',
        'delivery-mode' => 1,
    ]));

$loop = true;

pcntl_signal(SIGINT, function() use(&$loop) {
    echo 'Caught SIGINT, terminating...'.PHP_EOL;
    $loop = false;
});

while($loop) {
    $conn->serve(true, 0.1);
    pcntl_signal_dispatch();
}

$conn->close();
