<?php

declare(strict_types=1);

namespace Src\AccessControl\Interfaces\Http;

use App\Http\Requests\RegisterCheckInRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\JsonResponse;
use Src\AccessControl\Application\RegisterCheckIn;
use Src\AccessControl\Application\RegisterCheckInHandler;

final class RegisterCheckInController
{
    public function __construct(
        private readonly RegisterCheckInHandler $handler
    ) {
    }

    public function __invoke(RegisterCheckInRequest $request): JsonResponse
    {
        $command = new RegisterCheckIn(
            userId: $request->input('user_id'),
            credentialId: $request->input('credential_id'),
            gymId: $request->input('gym_id')
        );

        ($this->handler)($command);

        
        Artisan::call('outbox:publish');

        return response()->json([
            'status' => 'ok',
        ], 201);
    }
}