<?php

require_once './vendor/autoload.php';
require_once './class.php';
set_time_limit(0);

## Settings
$port = 2083;
$host = 'wss-public.executium.com';
$side = "asks";
$symbol = "btcusdt";
$exchange = "binance";
$orderbook_level=1;

$ws = new InitWs($port,$side,$symbol,$exchange,$orderbook_level);
$sid = $ws->prepareConnect($host);
$subscribeData = $ws->getSubscribeServer($host, $sid);

function orderbook_pushed($match)
{
    $data = json_decode($match[2], true);

    if($data[0] == 'dp')
    {
        $d = explode(",", trim($data[1]['d'], "[]"));
        $price = $d[0];
        $quantity = $d[1];
        $ago = $d[2];

        echo "n: {$data[1]['n']}, price: {$price}, quantity: {$quantity}, time: {$ago}" . PHP_EOL;
    }
}


$loop = React\EventLoop\Factory::create();
$reactConnector = new React\Socket\Connector($loop, ['tls' => ['verify_peer'=> false, 'verify_peer_name' => false,]]);

$connector = new \Ratchet\Client\Connector($loop, $reactConnector);
$connector($ws->createUrl($host, $sid, ['type' => 'wss']), ['Origin' => 'https://' . $host])
    ->then(function (Ratchet\Client\WebSocket $conn) use ($ws) {
        $conn->on('message', function ($msg) use ($conn, $ws) {
            $ws->debug("Received: {$msg}", 99);
            switch ($msg) {
                case '3probe':
                    $conn->send('5');
                    break;
                case '3':
                    $conn->send('2');
                    break;
                default:
                    $conn->close();
            }
        });

        $conn->on('close', function ($code = null, $reason = null) use ($ws) {
            $ws->debug("Connection closed ({$code} - {$reason})", 99);
        });

        $conn->send('2probe');
    }, function (\Exception $e) use ($loop) {
        echo "Could not connect: {$e->getMessage()}\n";
        $loop->stop();
    });

if (empty($subscribeData) || ($subscribeData[0] ?? '') !== 'obreq') {
    exit("Incorrect subscribe data.");
}

$subscribeHost = $subscribeData[1]['n'];
$subscribeSid = $ws->prepareSubscribe($subscribeHost);

$subscribeConnector = new \Ratchet\Client\Connector($loop, $reactConnector);
$subscribeConnector(
    $ws->createUrl($subscribeHost, $subscribeSid, ['type' => 'wss']),
    ['Origin' => 'https://' . $host]
)
->then(function (Ratchet\Client\WebSocket $conn) use ($ws, $loop, $subscribeData) {
    $conn->on('message', function ($msg) use ($conn, $ws) {
        $ws->debug("Subscribe Received: {$msg}", 99);
        switch ($msg) {
            case '3probe':
                $conn->send('5');
                break;
        }

        if (preg_match("|(\d+)(.*)|", $msg, $match) && !empty($match[2]))
        {
           orderbook_pushed($match);
        }
    });

    $conn->on('close', function ($code = null, $reason = null) use ($ws, $loop, $subscribeData)
    {
        $ws->debug("Subscribe Connection closed ({$code} - {$reason})", 99);
    });

    $loop->addPeriodicTimer(5, function () use ($conn) {
        $conn->send(2);
    });

    $conn->send('2probe');
}, function (\Exception $e) use ($loop) {
    echo "Could not connect: {$e->getMessage()}\n";
    $loop->stop();
});

$loop->run();
