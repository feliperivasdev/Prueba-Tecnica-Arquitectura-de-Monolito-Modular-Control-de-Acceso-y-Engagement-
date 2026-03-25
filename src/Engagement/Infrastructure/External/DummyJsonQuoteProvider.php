<?php

declare(strict_types=1);

namespace Src\Engagement\Infrastructure\External;

use Illuminate\Support\Facades\Http;
use Src\Engagement\Domain\Exception\QuotePayloadMalformed;
use Src\Engagement\Domain\Exception\QuoteProviderUnavailable;
use Src\Engagement\Domain\Model\MotivationalQuote;
use Src\Engagement\Domain\Port\QuoteProvider;
use Throwable;

final class DummyJsonQuoteProvider implements QuoteProvider
{
    public function fetchRandom(): MotivationalQuote
    {
        $url = (string) config('services.dummyjson.quote_url');

        try {
            $response = Http::timeout(2)->get($url);
        } catch (Throwable) {
            throw new QuoteProviderUnavailable('Quote API not reachable');
        }

        if (!$response->successful()) {
            throw new QuoteProviderUnavailable('Quote API returned error');
        }

        $data = $response->json();

        if (
            !is_array($data) ||
            !isset($data['quote']) ||
            !isset($data['author'])
        ) {
            throw new QuotePayloadMalformed('Invalid quote payload');
        }

        return new MotivationalQuote(
            text: (string) $data['quote'],
            author: (string) $data['author']
        );
    }
}
