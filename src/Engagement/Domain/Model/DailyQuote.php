<?php

declare(strict_types=1);

namespace Src\Engagement\Domain\Model;

use DateTimeImmutable;

final class DailyQuote
{
    private function __construct(
        private readonly string $id,
        private readonly string $checkInId,
        private readonly string $userId,
        private readonly string $quoteText,
        private readonly string $quoteAuthor,
        private readonly DateTimeImmutable $assignedAt
    ) {
    }

    public static function assign(
        string $id,
        string $checkInId,
        string $userId,
        string $quoteText,
        string $quoteAuthor,
        DateTimeImmutable $assignedAt
    ): self {
        return new self(
            id: $id,
            checkInId: $checkInId,
            userId: $userId,
            quoteText: $quoteText,
            quoteAuthor: $quoteAuthor,
            assignedAt: $assignedAt
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function checkInId(): string
    {
        return $this->checkInId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function quoteText(): string
    {
        return $this->quoteText;
    }

    public function quoteAuthor(): string
    {
        return $this->quoteAuthor;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }
}
