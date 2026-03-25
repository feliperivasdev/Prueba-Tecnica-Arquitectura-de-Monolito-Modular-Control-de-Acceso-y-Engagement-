<?php

declare(strict_types=1);

namespace Src\AccessControl\Interfaces\Console;

use Illuminate\Console\Command;
use Src\AccessControl\Application\Outbox\PublishOutboxMessagesHandler;

final class PublishOutboxMessagesCommand extends Command
{
    protected $signature = 'outbox:publish {--limit=100}';
    protected $description = 'Publish unpublished outbox messages';

    public function __construct(
        private readonly PublishOutboxMessagesHandler $handler
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $published = ($this->handler)((int) $this->option('limit'));

        $this->info(sprintf('Published %d outbox message(s).', $published));

        return self::SUCCESS;
    }
}
