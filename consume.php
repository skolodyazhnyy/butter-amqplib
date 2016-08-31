<?php

use AMQLib\Connection;
use AMQLib\InputOutput\SocketInputOutput;
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
//$io->setLogger(new Logger('io', [$stream]));

$conn = new AMQLib\Connection('//localhost', $io);
//$conn->setLogger(new Logger('connection', [$stream]));
$conn->open();

$ch = $conn->channel();
$ch->qos(0, 1, true);
$ch->exchange('rabbit')
    ->define('direct');

$ch->queue('rabbit')
    ->define(\AMQLib\Queue::FLAG_AUTO_DELETE)
    ->bind('rabbit');

$ch->queue('rabbit')
    ->consume(function(\AMQLib\Delivery $delivery) {
        echo $delivery->getBody() . PHP_EOL;
        $delivery->ack();
    });

$loop = true;

pcntl_signal(SIGINT, function() use(&$loop) {
    echo 'Caught SIGINT, terminating...'.PHP_EOL;
    $loop = false;
});

while($loop) {
    $conn->serve(true, 0.5);
    pcntl_signal_dispatch();
}

$conn->close();
