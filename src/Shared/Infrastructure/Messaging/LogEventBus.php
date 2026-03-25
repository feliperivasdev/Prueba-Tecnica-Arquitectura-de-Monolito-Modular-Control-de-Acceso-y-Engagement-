<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Messaging;

use Illuminate\Support\Facades\Log;
use Src\Engagement\Application\AssignDailyQuoteOnCheckInHandler;
use Src\Shared\Application\Messaging\EventBus;

final class LogEventBus implements EventBus
{
    public function __construct(
        private readonly AssignDailyQuoteOnCheckInHandler $assignDailyQuoteOnCheckInHandler
    ) {
    }

    public function publish(string $eventName, array $payload): void
    {
        Log::info('Outbox event published', [
            'event_name' => $eventName,
            'payload' => $payload,
        ]);

        if ($eventName === 'Src\\AccessControl\\Domain\\Event\\CheckInRegistered') {
            ($this->assignDailyQuoteOnCheckInHandler)(
                checkInId: $payload['check_in_id'],
                userId: $payload['user_id'],
                gymId: $payload['gym_id'],
                occurredAt: $payload['occurred_at']
            );
        }
    }
}
