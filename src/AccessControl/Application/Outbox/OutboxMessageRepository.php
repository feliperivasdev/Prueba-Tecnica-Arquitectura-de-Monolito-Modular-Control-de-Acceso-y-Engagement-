<?php

declare(strict_types=1);

namespace Src\AccessControl\Application\Outbox;

interface OutboxMessageRepository
{
    /** @return OutboxMessage[] */
    public function findUnpublished(int $limit): array;

    public function markAsPublished(string $messageId): void;
}
