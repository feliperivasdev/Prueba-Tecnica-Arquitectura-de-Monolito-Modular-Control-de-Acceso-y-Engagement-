<?php

declare(strict_types=1);

namespace Src\Engagement\Interfaces\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Src\Engagement\Application\AssignDailyQuoteOnCheckInHandler;
use Src\Engagement\Domain\Exception\QuotePayloadMalformed;
use Src\Engagement\Domain\Exception\QuoteProviderUnavailable;
use Throwable;

final class ConsumeCheckInRegisteredCommand extends Command
{
    protected $signature = 'engagement:consume-checkins {--max-messages=0}';
    protected $description = 'Consume CheckInRegistered events from RabbitMQ';

    public function __construct(
        private readonly AssignDailyQuoteOnCheckInHandler $handler
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $config = config('messaging.rabbitmq');
        $maxMessages = (int) $this->option('max-messages');
        $processed = 0;

        $connection = new AMQPStreamConnection(
            host: $config['host'],
            port: $config['port'],
            user: $config['user'],
            password: $config['password'],
            vhost: $config['vhost']
        );

        $channel = $connection->channel();
        $this->declareTopology($channel, $config);
        $channel->basic_qos(null, 1, null);

        $this->info('Waiting for CheckInRegistered events...');

        $channel->basic_consume(
            queue: $config['queue'],
            consumer_tag: '',
            no_local: false,
            no_ack: false,
            exclusive: false,
            nowait: false,
            callback: function (AMQPMessage $message) use (
                $channel,
                $config,
                $maxMessages,
                &$processed
            ): void {
                try {
                    $event = $this->decode($message->getBody());
                    $payload = $event['payload'];

                    ($this->handler)(
                        checkInId: (string) $payload['check_in_id'],
                        userId: (string) $payload['user_id'],
                        gymId: (string) $payload['gym_id'],
                        occurredAt: (string) $payload['occurred_at']
                    );

                    $channel->basic_ack($message->getDeliveryTag());
                    $processed++;
                } catch (QuoteProviderUnavailable|QuotePayloadMalformed $exception) {
                    $retryCount = $this->retryCount($message) + 1;

                    if ($retryCount > $config['max_retries']) {
                        $channel->basic_publish(
                            msg: $this->buildRetryMessage($message->getBody(), $retryCount),
                            exchange: '',
                            routing_key: $config['dlq']
                        );
                    } else {
                        $channel->basic_publish(
                            msg: $this->buildRetryMessage($message->getBody(), $retryCount),
                            exchange: '',
                            routing_key: $config['retry_queue']
                        );
                    }

                    $channel->basic_ack($message->getDeliveryTag());
                    Log::warning('Retrying CheckInRegistered processing', [
                        'retry_count' => $retryCount,
                        'message' => $exception->getMessage(),
                    ]);
                } catch (Throwable $exception) {
                    $channel->basic_nack($message->getDeliveryTag(), false, true);
                    Log::error('Unexpected CheckInRegistered processing error', [
                        'exception' => $exception->getMessage(),
                    ]);
                }

                if ($maxMessages > 0 && $processed >= $maxMessages) {
                    $channel->basic_cancel($message->getConsumerTag());
                }
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        $this->info(sprintf('Processed %d message(s).', $processed));

        return self::SUCCESS;
    }

    private function declareTopology(AMQPChannel $channel, array $config): void
    {
        $channel->exchange_declare($config['exchange'], 'direct', false, true, false);
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
    }

    /** @return array{event_name: string, payload: array<string, mixed>} */
    private function decode(string $body): array
    {
        try {
            $event = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new QuotePayloadMalformed('Invalid event payload', 0, $exception);
        }

        if (
            !is_array($event) ||
            !isset($event['event_name'], $event['payload']) ||
            !is_array($event['payload'])
        ) {
            throw new QuotePayloadMalformed('Missing event metadata');
        }

        if ($event['event_name'] !== 'Src\\AccessControl\\Domain\\Event\\CheckInRegistered') {
            throw new QuotePayloadMalformed('Unsupported event name');
        }

        return $event;
    }

    private function retryCount(AMQPMessage $message): int
    {
        $headers = $message->get('application_headers');

        if ($headers === null) {
            return 0;
        }

        $data = $headers->getNativeData();

        return (int) ($data['x-retry-count'] ?? 0);
    }

    private function buildRetryMessage(string $body, int $retryCount): AMQPMessage
    {
        return new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => 2,
            'application_headers' => new \PhpAmqpLib\Wire\AMQPTable([
                'x-retry-count' => $retryCount,
            ]),
        ]);
    }
}
