<?php

declare(strict_types=1);

namespace Src\AccessControl\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\AccessControl\Domain\Event\CheckInRegistered;
use Src\AccessControl\Domain\Repository\OutboxRepository;

final class PdoOutboxRepository implements OutboxRepository
{
    public function store(object $event): void
    {
        DB::table('outbox_messages')->insert([
            'id' => (string) str()->uuid(),
            'event_name' => $this->eventName($event),
            'payload' => json_encode($this->payload($event), JSON_THROW_ON_ERROR),
            'occurred_at' => $this->occurredAt($event),
            'published_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function eventName(object $event): string
    {
        return $event::class;
    }

    private function payload(object $event): array
    {
        if ($event instanceof CheckInRegistered) {
            return [
                'check_in_id' => $event->checkInId,
                'user_id' => $event->userId,
                'gym_id' => $event->gymId,
                'occurred_at' => $event->occurredAt->format(DATE_ATOM),
            ];
        }

        throw new \InvalidArgumentException('Unsupported outbox event: ' . $event::class);
    }

    private function occurredAt(object $event): string
    {
        if ($event instanceof CheckInRegistered) {
            return $event->occurredAt->format('Y-m-d H:i:s');
        }

        throw new \InvalidArgumentException('Unsupported outbox event: ' . $event::class);
    }
}
