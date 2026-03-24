<?php

declare(strict_types=1);

namespace Src\Shared\Domain;

interface UuidGenerator
{
    public function generate(): string;
}