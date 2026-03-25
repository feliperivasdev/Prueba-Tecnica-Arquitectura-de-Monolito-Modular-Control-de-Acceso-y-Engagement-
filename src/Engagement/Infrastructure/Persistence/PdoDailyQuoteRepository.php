<?php

declare(strict_types=1);

namespace Src\Engagement\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Engagement\Domain\Model\DailyQuote;
use Src\Engagement\Domain\Repository\DailyQuoteRepository;

final class PdoDailyQuoteRepository implements DailyQuoteRepository
{
    public function save(DailyQuote $dailyQuote): void
    {
        DB::table('daily_quotes')->insert([
            'id' => $dailyQuote->id(),
            'check_in_id' => $dailyQuote->checkInId(),
            'user_id' => $dailyQuote->userId(),
            'quote_text' => $dailyQuote->quoteText(),
            'quote_author' => $dailyQuote->quoteAuthor(),
            'assigned_at' => $dailyQuote->assignedAt(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
