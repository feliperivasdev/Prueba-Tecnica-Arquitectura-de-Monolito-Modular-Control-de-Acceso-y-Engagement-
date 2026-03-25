<?php

declare(strict_types=1);

namespace Src\AccessControl\Application\Outbox;

use DateTimeImmutable;

final class OutboxMessage
{
    public function __construct(
        public readonly string $id,
        public readonly string $eventName,
        public readonly array $payload,
        public readonly DateTimeImmutable $occurredAt
    ) {
    }
}
