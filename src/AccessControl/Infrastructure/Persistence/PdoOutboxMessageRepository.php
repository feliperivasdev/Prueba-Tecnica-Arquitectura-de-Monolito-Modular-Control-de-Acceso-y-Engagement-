<?php

declare(strict_types=1);

namespace Src\AccessControl\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\AccessControl\Application\Outbox\OutboxMessage;
use Src\AccessControl\Application\Outbox\OutboxMessageRepository;

final class PdoOutboxMessageRepository implements OutboxMessageRepository
{
    public function findUnpublished(int $limit): array
    {
        $rows = DB::table('outbox_messages')
            ->select(['id', 'event_name', 'payload', 'occurred_at'])
            ->whereNull('published_at')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $messages = [];

        foreach ($rows as $row) {
            $messages[] = new OutboxMessage(
                id: $row->id,
                eventName: $row->event_name,
                payload: json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR),
                occurredAt: new DateTimeImmutable($row->occurred_at)
            );
        }

        return $messages;
    }

    public function markAsPublished(string $messageId): void
    {
        DB::table('outbox_messages')
            ->where('id', $messageId)
            ->update([
                'published_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
