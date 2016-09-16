<?php

if ($argc < 4) {
    die(sprintf("Usage: %s <amqp-url> <queue-name> <number-of-messages> <message-size>", $argv[0]).PHP_EOL);
}

list($cmd, $url, $queue, $messages, $size) = $argv;

require_once __DIR__.'/../vendor/autoload.php';

$connection = \ButterAMQP\ConnectionBuilder::make()
    ->create($url)
    ->open();

$channel = $connection->channel();

$queue = $channel->queue($queue)
    ->define()
    ->purge();

$payload = str_repeat('.', $size);

for ($i = 0; $i < $messages; $i++) {
    $message = new \ButterAMQP\Message($payload, [
        'headers' => [
            'x-index' => $i,
            'x-total' => $messages,
        ],
    ]);

    $channel->publish($message, '', (string) $queue);
}

$channel->close();
$connection->close();
