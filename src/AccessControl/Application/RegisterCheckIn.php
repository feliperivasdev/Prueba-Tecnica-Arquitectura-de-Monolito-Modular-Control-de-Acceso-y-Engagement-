<?php

namespace Src\AccessControl\Application;

final class RegisterCheckIn
{
    public function __construct(
        public readonly string $userId,
        public readonly string $credentialId,
        public readonly string $gymId
    ) {}
}