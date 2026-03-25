<?php

declare(strict_types=1);

namespace Src\AccessControl\Application\Outbox;

use Src\Shared\Application\Messaging\EventBus;
use Throwable;

final class PublishOutboxMessagesHandler
{
    public function __construct(
        private readonly OutboxMessageRepository $outboxMessageRepository,
        private readonly EventBus $eventBus
    ) {
    }

    public function __invoke(int $limit = 100): int
    {
        $messages = $this->outboxMessageRepository->findUnpublished($limit);
        $publishedCount = 0;

        foreach ($messages as $message) {
            try {
                $this->eventBus->publish(
                    eventName: $message->eventName,
                    payload: $message->payload
                );

                $this->outboxMessageRepository->markAsPublished($message->id);
                $publishedCount++;
            } catch (Throwable) {
                continue;
            }
        }

        return $publishedCount;
    }
}
