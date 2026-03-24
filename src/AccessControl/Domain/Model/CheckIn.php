<?php

declare(strict_types=1);

namespace Src\AccessControl\Domain\Model;

use DateTimeImmutable;
use Src\AccessControl\Domain\Event\CheckInRegistered;

final class CheckIn
{

    private array $domainEvents = [];

    private function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $credentialId,
        private readonly string $gymId,
        private readonly DateTimeImmutable $occurredAt
    ) {
    }

    public static function register(
        string $id,
        string $userId,
        string $credentialId,
        string $gymId,
        DateTimeImmutable $occurredAt
    ): self {
        $checkIn = new self(
            id: $id,
            userId: $userId,
            credentialId: $credentialId,
            gymId: $gymId,
            occurredAt: $occurredAt
        );

        $checkIn->recordEvent(
            new CheckInRegistered(
                checkInId: $id,
                userId: $userId,
                gymId: $gymId,
                occurredAt: $occurredAt
            )
        );

        return $checkIn;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function credentialId(): string
    {
        return $this->credentialId;
    }

    public function gymId(): string
    {
        return $this->gymId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /** @return object[] */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}