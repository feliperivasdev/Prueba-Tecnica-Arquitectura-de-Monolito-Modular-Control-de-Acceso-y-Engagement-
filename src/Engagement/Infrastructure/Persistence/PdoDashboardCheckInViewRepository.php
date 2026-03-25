<?php

declare(strict_types=1);

namespace Src\Engagement\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Engagement\Application\ReadModel\DashboardCheckInViewRepository;

final class PdoDashboardCheckInViewRepository implements DashboardCheckInViewRepository
{
    public function findByUserId(string $userId): array
    {
        return DB::table('dashboard_checkin_view')
            ->select([
                'check_in_id',
                'user_id',
                'gym_id',
                'checked_in_at',
                'quote_text',
                'quote_author',
                'quote_assigned_at',
            ])
            ->where('user_id', $userId)
            ->orderByDesc('checked_in_at')
            ->get()
            ->map(static fn (object $row): array => [
                'check_in_id' => $row->check_in_id,
                'user_id' => $row->user_id,
                'gym_id' => $row->gym_id,
                'checked_in_at' => $row->checked_in_at,
                'quote_text' => $row->quote_text,
                'quote_author' => $row->quote_author,
                'quote_assigned_at' => $row->quote_assigned_at,
            ])
            ->all();
    }
}
