<?php

declare(strict_types=1);

namespace Src\AccessControl\Domain\Repository;

use Src\AccessControl\Domain\Model\CheckIn;

interface CheckInRepository
{
    public function save(CheckIn $checkIn): void;
}