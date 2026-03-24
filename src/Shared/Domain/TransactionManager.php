<?php

declare(strict_types=1);

namespace Src\Shared\Domain;

interface TransactionManager
{
    public function run(callable $callback): mixed;
}