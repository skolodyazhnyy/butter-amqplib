<?php

if ($argc < 3) {
    die(sprintf("Usage: %s <amqp-url> <queue-name> <number-of-messages>", $argv[0]).PHP_EOL);
}

list($cmd, $url, $queue, $messages) = $argv;

require_once __DIR__.'/../vendor/autoload.php';

$start = microtime(true);

$connection = \ButterAMQP\ConnectionBuilder::make()
    ->create($url)
    ->open();

$channel = $connection->channel();

$queue = $channel->queue($queue)
    ->define();

$consumer = $channel->consume($queue, function(\ButterAMQP\Delivery $delivery) use(&$messages) {
    $messages--;

    $delivery->ack();

    if ($messages <= 0) {
        $delivery->cancel();
    }
});

while ($consumer->isActive()) {
    $connection->serve();
}

$channel->close();
$connection->close();

echo ' - ' . number_format(microtime(true) - $start, 5, '.', ''). ' seconds'.PHP_EOL;
echo ' - ' . $messages.' messages left'.PHP_EOL;
echo ' - ' . memory_get_peak_usage().' memory usage peak'.PHP_EOL;
echo PHP_EOL;
