<?php

declare(strict_types=1);

namespace Src\Engagement\Domain\Repository;

use Src\Engagement\Domain\Model\DailyQuote;

interface DailyQuoteRepository
{
    public function save(DailyQuote $dailyQuote): void;
}
