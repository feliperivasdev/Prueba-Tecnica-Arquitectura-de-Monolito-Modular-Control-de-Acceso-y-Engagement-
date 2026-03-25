<?php

declare(strict_types=1);

namespace Src\Engagement\Application\ReadModel;

final class GetUserDashboardCheckInsQueryHandler
{
    public function __construct(
        private readonly DashboardCheckInViewRepository $repository
    ) {
    }

    public function __invoke(string $userId): array
    {
        return $this->repository->findByUserId($userId);
    }
}
