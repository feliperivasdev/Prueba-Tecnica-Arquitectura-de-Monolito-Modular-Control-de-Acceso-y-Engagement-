<?php

declare(strict_types=1);

namespace Src\Engagement\Infrastructure\External;

use Src\Engagement\Domain\Model\MotivationalQuote;
use Src\Engagement\Domain\Port\QuoteProvider;

final class FakeQuoteProvider implements QuoteProvider
{
    public function fetchRandom(): MotivationalQuote
    {
        return new MotivationalQuote(
            text: 'Discipline is stronger than motivation.',
            author: 'System'
        );
    }
}
