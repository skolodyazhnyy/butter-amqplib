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

$socket = 'tcp://'.$argv[1];

echo sprintf('Starting echo-server at "%s"', $socket).PHP_EOL;

$server = stream_socket_server($socket);
$control = stream_socket_accept($server, 2);

if ($control === false) {
    throw new \Exception('Control connection time expired');
}

echo 'Control connection established'.PHP_EOL;

$client = stream_socket_accept($server, 2);

if ($client === false) {
    throw new \Exception('Client connection time expired');
}

echo 'Client connection established'.PHP_EOL;

stream_set_blocking($control,  false);
stream_set_blocking($client, false);

$readers = [$client, $control];
$writers = [$client, $control];
$except = null;

while (true) {
    $select = stream_select($readers, $writers, $except, 0, 500000);

    if ($select === false) {
        throw new \Exception('An error occur during stream select');
    }

    $input = stream_get_contents($control);
    $output = stream_get_contents($client);

    if (($p = strpos($input, "\xCE")) !== false) {
        if ($p) {
            fwrite($client, substr($input, 0, $p));
            fflush($client);
        }

        break;
    }

    fwrite($control, $output);
    fflush($control);

    fwrite($client, $input);
    fflush($client);

    if (strlen($input)) {
        echo strlen($input).' bytes send to the client'.PHP_EOL;
    }

    if (strlen($output)) {
        echo strlen($output).' bytes received from the client'.PHP_EOL;
    }
}

echo 'Out of main loop, closing descriptors and terminating...'.PHP_EOL;

fclose($control);
fclose($client);
fclose($server);

echo 'Complete'.PHP_EOL;
