<?php

declare(strict_types=1);

namespace Src\Engagement\Application\ReadModel;

interface DashboardCheckInViewRepository
{
    public function findByUserId(string $userId): array;
}
