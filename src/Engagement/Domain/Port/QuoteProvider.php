<?php

declare(strict_types=1);

namespace Src\Engagement\Domain\Port;

use Src\Engagement\Domain\Model\MotivationalQuote;

interface QuoteProvider
{
    public function fetchRandom(): MotivationalQuote;
}
