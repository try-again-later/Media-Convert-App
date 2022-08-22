<?php

declare(strict_types = 1);

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use React\ZMQ\Context as ZmqContext;

use TryAgainLater\MediaConvertAppWebsockets\Server;

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Loop::get();
$server = new Server;

$context = new ZmqContext($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:5555');
$pull->on('message', $server->onFileUpload(...));

$webSock = new SocketServer('0.0.0.0:8081', loop: $loop);
$webServer = new IoServer(
    new HttpServer(
        new WsServer(
            $server
        ),
    ),
    $webSock,
);

$loop->run();
