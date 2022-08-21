<?php

declare(strict_types = 1);

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

use TryAgainLater\MediaConvertAppWebsockets\Server;

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$server = IoServer::factory(new HttpServer(new WsServer(new Server())), 8081);
$server->run();
