<?php

declare(strict_types=1);

namespace Src\AccessControl\Domain\Repository;

interface OutboxRepository
{
    public function store(object $event): void;
}