<?php
declare(strict_types=1);

namespace App;

final class QueueConnectionFactory
{
    public function create(): \AMQPConnection
    {
        $lastError = null;
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $connection = new \AMQPConnection([
                'host' => getenv('RABBITMQ_HOST') ?: 'rabbitmq',
                'port' => (int)(getenv('RABBITMQ_PORT') ?: 5672),
                'login' => getenv('RABBITMQ_USER') ?: 'app',
                'password' => getenv('RABBITMQ_PASSWORD') ?: 'secret',
                'vhost' => '/',
                'connect_timeout' => 3,
            ]);
            try {
                $connection->connect();
                return $connection;
            } catch (\AMQPConnectionException $exception) {
                $lastError = $exception;
                sleep(1);
            }
        }
        throw $lastError;
    }
}
