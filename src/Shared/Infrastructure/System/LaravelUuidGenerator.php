<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\System;

use Illuminate\Support\Str;
use Src\Shared\Domain\UuidGenerator;

final class LaravelUuidGenerator implements UuidGenerator
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
