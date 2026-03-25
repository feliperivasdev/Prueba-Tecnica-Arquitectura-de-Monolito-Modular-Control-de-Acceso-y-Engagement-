<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Src\Shared\Application\Messaging\EventBus;
use Throwable;

final class RabbitMqEventBus implements EventBus
{
    public function publish(string $eventName, array $payload): void
    {
        $config = config('messaging.rabbitmq');

        $connection = null;
        $channel = null;

        try {
            $connection = new AMQPStreamConnection(
                host: $config['host'],
                port: $config['port'],
                user: $config['user'],
                password: $config['password'],
                vhost: $config['vhost']
            );

            $channel = $connection->channel();
            $channel->exchange_declare(
                exchange: $config['exchange'],
                type: 'direct',
                passive: false,
                durable: true,
                auto_delete: false
            );
            $channel->queue_declare($config['dlq'], false, true, false, false);
            $channel->queue_declare(
                $config['retry_queue'],
                false,
                true,
                false,
                false,
                false,
                [
                    'x-message-ttl' => ['I', $config['retry_delay_ms']],
                    'x-dead-letter-exchange' => ['S', $config['exchange']],
                    'x-dead-letter-routing-key' => ['S', $config['routing_key']],
                ]
            );
            $channel->queue_declare(
                $config['queue'],
                false,
                true,
                false,
                false,
                false,
                [
                    'x-dead-letter-exchange' => ['S', ''],
                    'x-dead-letter-routing-key' => ['S', $config['dlq']],
                ]
            );
            $channel->queue_bind($config['queue'], $config['exchange'], $config['routing_key']);

            $message = new AMQPMessage(
                body: json_encode([
                    'event_name' => $eventName,
                    'payload' => $payload,
                ], JSON_THROW_ON_ERROR),
                properties: [
                    'content_type' => 'application/json',
                    'delivery_mode' => 2,
                ]
            );

            $channel->basic_publish(
                msg: $message,
                exchange: $config['exchange'],
                routing_key: $config['routing_key']
            );
        } catch (Throwable $exception) {
            throw new RuntimeException('Unable to publish message to RabbitMQ', 0, $exception);
        } finally {
            if ($channel !== null) {
                $channel->close();
            }

            if ($connection !== null) {
                $connection->close();
            }
        }
    }
}
