<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Importaciones de AccessControl
use Src\AccessControl\Application\Outbox\OutboxMessageRepository;
use Src\AccessControl\Domain\Repository\CheckInRepository;
use Src\AccessControl\Domain\Repository\OutboxRepository;
use Src\AccessControl\Infrastructure\Persistence\PdoCheckInRepository;
use Src\AccessControl\Infrastructure\Persistence\PdoOutboxMessageRepository;
use Src\AccessControl\Infrastructure\Persistence\PdoOutboxRepository;

// Importaciones de Engagement
use Src\Engagement\Domain\Port\QuoteProvider;
use Src\Engagement\Domain\Repository\DailyQuoteRepository;
use Src\Engagement\Domain\Repository\DashboardProjectionRepository;
use Src\Engagement\Infrastructure\External\FakeQuoteProvider;
use Src\Engagement\Infrastructure\Persistence\PdoDailyQuoteRepository;
use Src\Engagement\Infrastructure\Persistence\PdoDashboardProjectionRepository;

// Importaciones de Shared
use Src\Shared\Application\Messaging\EventBus;
use Src\Shared\Domain\Clock;
use Src\Shared\Domain\TransactionManager;
use Src\Shared\Domain\UuidGenerator;
use Src\Shared\Infrastructure\Messaging\LogEventBus;
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
        
        // AccessControl
        $this->app->bind(CheckInRepository::class, PdoCheckInRepository::class);
        $this->app->bind(OutboxRepository::class, PdoOutboxRepository::class);
        $this->app->bind(OutboxMessageRepository::class, PdoOutboxMessageRepository::class);
        
        // Engagement
        $this->app->bind(QuoteProvider::class, FakeQuoteProvider::class);
        $this->app->bind(DailyQuoteRepository::class, PdoDailyQuoteRepository::class);
        $this->app->bind(DashboardProjectionRepository::class, PdoDashboardProjectionRepository::class);
        
        // Shared
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
        $this->app->bind(EventBus::class, LogEventBus::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}