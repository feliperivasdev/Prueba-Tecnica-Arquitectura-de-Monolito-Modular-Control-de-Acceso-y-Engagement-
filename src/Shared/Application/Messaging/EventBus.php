<?php

declare(strict_types=1);

namespace Src\Shared\Application\Messaging;

interface EventBus
{
    public function publish(string $eventName, array $payload): void;
}
