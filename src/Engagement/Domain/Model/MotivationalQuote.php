<?php

declare(strict_types=1);

namespace Src\Engagement\Domain\Model;

final class MotivationalQuote
{
    public function __construct(
        private readonly string $text,
        private readonly string $author
    ) {
    }

    public function text(): string
    {
        return $this->text;
    }

    public function author(): string
    {
        return $this->author;
    }
}
