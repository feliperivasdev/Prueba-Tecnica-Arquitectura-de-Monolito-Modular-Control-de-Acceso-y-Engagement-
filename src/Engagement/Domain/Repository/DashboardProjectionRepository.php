<?php

declare(strict_types=1);

namespace Src\Engagement\Domain\Repository;

use DateTimeImmutable;

interface DashboardProjectionRepository
{
    public function upsert(
        string $checkInId,
        string $userId,
        string $gymId,
        DateTimeImmutable $checkedInAt,
        string $quoteText,
        string $quoteAuthor,
        DateTimeImmutable $quoteAssignedAt
    ): void;
}
