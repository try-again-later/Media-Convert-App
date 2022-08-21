<?php

declare(strict_types = 1);

namespace TryAgainLater\MediaConvertAppWebsockets;

use Exception;
use SplObjectStorage;

use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class Server implements MessageComponentInterface
{
    protected SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $connection)
    {
        $this->clients->attach($connection);

        echo "New connection: {$connection->resourceId}." . PHP_EOL;
    }

    public function onMessage(ConnectionInterface $from, $message)
    {
        $messageString = (string) $message;
        echo "Got message: $messageString." . PHP_EOL;
    }

    public function onClose(ConnectionInterface $connection)
    {
        $this->clients->detach($connection);

        echo "Closed connection: {$connection->resourceId}." . PHP_EOL;
    }

    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        echo
            "Got error \"{$exception->getMessage()}\" from connection: {$connection->resourceId}." .
            PHP_EOL;

        $connection->close();
    }
}
