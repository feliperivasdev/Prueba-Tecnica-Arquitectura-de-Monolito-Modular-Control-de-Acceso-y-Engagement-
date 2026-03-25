<?php

declare(strict_types=1);

namespace Src\Engagement\Interfaces\Http;

use Illuminate\Http\JsonResponse;
use Src\Engagement\Application\ReadModel\GetUserDashboardCheckInsQueryHandler;

final class GetUserDashboardCheckInsController
{
    public function __construct(
        private readonly GetUserDashboardCheckInsQueryHandler $handler
    ) {
    }

    public function __invoke(string $userId): JsonResponse
    {
        return response()->json([
            'data' => ($this->handler)($userId),
        ]);
    }
}
