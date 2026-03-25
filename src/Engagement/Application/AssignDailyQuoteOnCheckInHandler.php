<?php

declare(strict_types=1);

namespace Src\Engagement\Application;

use Src\Engagement\Domain\Model\DailyQuote;
use Src\Engagement\Domain\Port\QuoteProvider;
use Src\Engagement\Domain\Repository\DailyQuoteRepository;
use Src\Engagement\Domain\Repository\DashboardProjectionRepository;
use Src\Shared\Domain\Clock;
use Src\Shared\Domain\UuidGenerator;

final class AssignDailyQuoteOnCheckInHandler
{
    public function __construct(
        private readonly QuoteProvider $quoteProvider,
        private readonly DailyQuoteRepository $dailyQuoteRepository,
        private readonly DashboardProjectionRepository $dashboardProjectionRepository,
        private readonly Clock $clock,
        private readonly UuidGenerator $uuidGenerator
    ) {
    }

    public function __invoke(
        string $checkInId,
        string $userId,
        string $gymId,
        string $occurredAt
    ): void {
        $quote = $this->quoteProvider->fetchRandom();
        $assignedAt = $this->clock->now();

        $dailyQuote = DailyQuote::assign(
            id: $this->uuidGenerator->generate(),
            checkInId: $checkInId,
            userId: $userId,
            quoteText: $quote->text(),
            quoteAuthor: $quote->author(),
            assignedAt: $assignedAt
        );

        $this->dailyQuoteRepository->save($dailyQuote);

        $this->dashboardProjectionRepository->upsert(
            checkInId: $checkInId,
            userId: $userId,
            gymId: $gymId,
            checkedInAt: new \DateTimeImmutable($occurredAt),
            quoteText: $quote->text(),
            quoteAuthor: $quote->author(),
            quoteAssignedAt: $assignedAt
        );
    }
}
