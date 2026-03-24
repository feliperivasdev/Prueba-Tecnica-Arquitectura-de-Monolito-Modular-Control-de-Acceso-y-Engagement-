<?php

declare(strict_types=1);

namespace Src\AccessControl\Domain\Event;

use DateTimeImmutable;

final class CheckInRegistered
{
    public function __construct(
        public readonly string $checkInId,
        public readonly string $userId,
        public readonly string $gymId,
        public readonly DateTimeImmutable $occurredAt
    ) {
    }
}