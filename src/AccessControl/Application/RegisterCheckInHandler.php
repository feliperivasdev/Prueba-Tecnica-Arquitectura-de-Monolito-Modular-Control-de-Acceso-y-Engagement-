<?php

declare(strict_types=1);

namespace Src\AccessControl\Application;

use Src\AccessControl\Domain\Model\CheckIn;
use Src\AccessControl\Domain\Repository\CheckInRepository;
use Src\AccessControl\Domain\Repository\OutboxRepository;
use Src\Shared\Domain\Clock;
use Src\Shared\Domain\TransactionManager;
use Src\Shared\Domain\UuidGenerator;

final class RegisterCheckInHandler
{
    public function __construct(
        private readonly CheckInRepository $checkInRepository,
        private readonly OutboxRepository $outboxRepository,
        private readonly Clock $clock,
        private readonly UuidGenerator $uuidGenerator,
        private readonly TransactionManager $transactionManager
    ) {
    }

    public function __invoke(RegisterCheckIn $command): void
    {
        $this->transactionManager->run(function () use ($command): void {
            $checkIn = CheckIn::register(
                id: $this->uuidGenerator->generate(),
                userId: $command->userId,
                credentialId: $command->credentialId,
                gymId: $command->gymId,
                occurredAt: $this->clock->now()
            );

            $this->checkInRepository->save($checkIn);

            foreach ($checkIn->releaseEvents() as $event) {
                $this->outboxRepository->store($event);
            }
        });
    }
}