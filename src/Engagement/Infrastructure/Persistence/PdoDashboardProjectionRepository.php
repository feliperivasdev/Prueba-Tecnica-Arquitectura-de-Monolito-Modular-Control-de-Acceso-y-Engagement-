<?php

declare(strict_types=1);

namespace Src\Engagement\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Engagement\Domain\Repository\DashboardProjectionRepository;

final class PdoDashboardProjectionRepository implements DashboardProjectionRepository
{
    public function upsert(
        string $checkInId,
        string $userId,
        string $gymId,
        DateTimeImmutable $checkedInAt,
        string $quoteText,
        string $quoteAuthor,
        DateTimeImmutable $quoteAssignedAt
    ): void {
        DB::table('dashboard_checkin_view')->updateOrInsert(
            ['check_in_id' => $checkInId],
            [
                'user_id' => $userId,
                'gym_id' => $gymId,
                'checked_in_at' => $checkedInAt,
                'quote_text' => $quoteText,
                'quote_author' => $quoteAuthor,
                'quote_assigned_at' => $quoteAssignedAt,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
