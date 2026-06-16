<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;

$ws = new Worker('websocket://0.0.0.0:8081');

$ws->count = 1;

$ws->onWorkerStart = function () {
    echo "Workerman WebSocket server started at ws://0.0.0.0:8081\n";
};

$ws->onConnect = function ($connection) {
    $connection->send(json_encode([
        'type' => 'connected',
        'message' => 'WebSocket connected'
    ], JSON_UNESCAPED_UNICODE));
};

$ws->onMessage = function ($connection, $message) {
    $redisHost = getenv('REDIS_HOST') ?: 'redis';
    $redisPort = (int)(getenv('REDIS_PORT') ?: 6379);

    $redis = new Redis();
    $redis->connect($redisHost, $redisPort);

    $payload = [
        'type' => 'message',
        'message' => $message,
        'server_time' => date(DATE_ATOM),
    ];

    $redis->publish('game-events', json_encode($payload, JSON_UNESCAPED_UNICODE));

    $connection->send(json_encode($payload, JSON_UNESCAPED_UNICODE));

    $redis->close();
};

Worker::runAll();
