<?php

declare(strict_types = 1);

use Dotenv\Dotenv;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('PROJECT_ROOT_PATH', dirname(__DIR__, levels: 2) . DIRECTORY_SEPARATOR);

require_once ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$dotenv = Dotenv::createImmutable(PROJECT_ROOT_PATH);
$dotenv->safeLoad();
$dotenv->required(['RABBITMQ_USER', 'RABBITMQ_PASSWORD', 'RABBITMQ_HOST', 'RABBITMQ_PORT']);

$connection = new AMQPStreamConnection(
    host: $_ENV['RABBITMQ_HOST'],
    port: intval($_ENV['RABBITMQ_PORT']),
    user: $_ENV['RABBITMQ_USER'],
    password: $_ENV['RABBITMQ_PASSWORD'],
);

$channel = $connection->channel();
$channel->queue_declare(
    queue: 'tasks',
    auto_delete: false,
    durable: true,
);
$channel->basic_qos(prefetch_count: 1, prefetch_size: null, a_global: null);

function processImage(AMQPMessage $message)
{
    echo "Received message: \"{$message->body}\"." . PHP_EOL;

    sleep(seconds: 1); // TODO

    $message->ack();
    echo "Done." . PHP_EOL;
}

$channel->basic_consume(
    queue: 'tasks',
    no_ack: false,
    callback: processImage(...),
);

echo 'Waiting for tasks...' . PHP_EOL;

while ($channel->is_open()) {
    $channel->wait();
}
