<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Services;

final readonly class Feed
{
    public function __construct(
        public string $xml,
        public int $builtAt,
        public int $maxAge,
    ) {}

    public function etag(): string
    {
        return hash('xxh128', $this->xml);
    }
}
