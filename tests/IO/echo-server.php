<?php

if (!isset($argv[1])) {
    echo 'Usage: php echo-server.php <host>:<port>'.PHP_EOL.PHP_EOL;
    die(-1);
}

$run = true;
$socket = 'tcp://'.$argv[1];

pcntl_signal(SIGINT, function () use (&$run) {
    $run = false;
});

$server = stream_socket_server($socket);
$control = stream_socket_accept($server, 2);

if ($control === false) {
    throw new \Exception('Control connection time expired');
}

$client = stream_socket_accept($server, 2);

if ($client === false) {
    throw new \Exception('Client connection time expired');
}

$input = STDIN;
$output = STDOUT;

stream_set_blocking($control,  false);
stream_set_blocking($client, false);

$readers = [$client, $control];
$writers = [$client, $control];
$except = null;

while ($run) {
    $select = stream_select($readers, $writers, $except, 0, 5000000);

    if ($select === false) {
        throw new \Exception('An error occur during stream select');
    }

    stream_copy_to_stream($control, $client);
    stream_copy_to_stream($client, $control);

    pcntl_signal_dispatch();
}

fclose($control);
fclose($client);
fclose($server);
