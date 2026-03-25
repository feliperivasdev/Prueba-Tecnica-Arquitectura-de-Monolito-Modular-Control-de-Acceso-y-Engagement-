<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// Importaciones de AccessControl
use Src\AccessControl\Domain\Repository\CheckInRepository;
use Src\AccessControl\Domain\Repository\OutboxRepository;
use Src\AccessControl\Infrastructure\Persistence\PdoCheckInRepository;
use Src\AccessControl\Infrastructure\Persistence\PdoOutboxRepository;
// Importaciones de Shared
use Src\Shared\Domain\Clock;
use Src\Shared\Domain\TransactionManager;
use Src\Shared\Domain\UuidGenerator;
use Src\Shared\Infrastructure\Persistence\LaravelTransactionManager;
use Src\Shared\Infrastructure\System\LaravelUuidGenerator;
use Src\Shared\Infrastructure\System\SystemClock;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registro de los Binds para la Inyección de Dependencias
        $this->app->bind(CheckInRepository::class, PdoCheckInRepository::class);
        $this->app->bind(OutboxRepository::class, PdoOutboxRepository::class);
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
