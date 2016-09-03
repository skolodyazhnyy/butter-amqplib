<?php
/*
 * echo-server.php setup a simple server which accepts exactly two client connections and tunnels
 * all traffic from one to another. It used for to perform integration test for classes which implement
 * network communication. One connection is established as control and another one as client. Control
 * connection established by test to setup test environment. Client connection is established by subject
 * of test.
 *
 * See TestCast.php for more details.
 */

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
