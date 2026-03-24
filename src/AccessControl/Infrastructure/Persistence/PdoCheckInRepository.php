<?php

declare(strict_types=1);

namespace Src\AccessControl\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\AccessControl\Domain\Model\CheckIn;
use Src\AccessControl\Domain\Repository\CheckInRepository;

final class PdoCheckInRepository implements CheckInRepository
{
    public function save(CheckIn $checkIn): void
    {
        DB::table('checkins')->insert([
            'id' => $checkIn->id(),
            'user_id' => $checkIn->userId(),
            'credential_id' => $checkIn->credentialId(),
            'gym_id' => $checkIn->gymId(),
            'occurred_at' => $checkIn->occurredAt(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}