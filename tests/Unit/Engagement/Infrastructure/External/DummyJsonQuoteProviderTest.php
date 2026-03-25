<?php

declare(strict_types=1);

namespace Tests\Unit\Engagement\Infrastructure\External;

use Illuminate\Support\Facades\Http;
use Src\Engagement\Domain\Exception\QuotePayloadMalformed;
use Src\Engagement\Domain\Exception\QuoteProviderUnavailable;
use Src\Engagement\Infrastructure\External\DummyJsonQuoteProvider;
use Tests\TestCase;

final class DummyJsonQuoteProviderTest extends TestCase
{
    public function test_it_returns_a_quote_when_payload_is_valid(): void
    {
        Http::fake([
            '*' => Http::response([
                'quote' => 'No pain no gain',
                'author' => 'Unknown',
            ], 200),
        ]);

        $provider = new DummyJsonQuoteProvider();
        $quote = $provider->fetchRandom();

        $this->assertSame('No pain no gain', $quote->text());
        $this->assertSame('Unknown', $quote->author());
    }

    public function test_it_throws_when_network_timeout_or_connection_fails(): void
    {
        Http::fake(static fn () => throw new \RuntimeException('timeout'));

        $provider = new DummyJsonQuoteProvider();

        $this->expectException(QuoteProviderUnavailable::class);
        $provider->fetchRandom();
    }

    public function test_it_throws_when_provider_returns_server_error(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $provider = new DummyJsonQuoteProvider();

        $this->expectException(QuoteProviderUnavailable::class);
        $provider->fetchRandom();
    }

    public function test_it_throws_when_payload_contract_is_invalid(): void
    {
        Http::fake([
            '*' => Http::response([
                'text' => 'wrong field',
            ], 200),
        ]);

        $provider = new DummyJsonQuoteProvider();

        $this->expectException(QuotePayloadMalformed::class);
        $provider->fetchRandom();
    }
}
