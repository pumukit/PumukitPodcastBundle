<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Services;

class ConfigurationService
{
    public function __construct(
        private readonly string $channelTitle,
        private readonly string $channelDescription,
        private readonly string $channelCopyright,
        private readonly string $itunesCategory,
        private readonly string $itunesSummary,
        private readonly string $itunesSubtitle,
        private readonly string $itunesAuthor,
        private readonly bool $itunesExplicit,
    ) {}

    public function channelTitle(): string
    {
        return $this->channelTitle;
    }

    public function channelDescription(): string
    {
        return $this->channelDescription;
    }

    public function channelCopyright(): string
    {
        return $this->channelCopyright;
    }

    public function itunesCategory(): string
    {
        return $this->itunesCategory;
    }

    public function itunesSummary(): string
    {
        return $this->itunesSummary;
    }

    public function itunesSubtitle(): string
    {
        return $this->itunesSubtitle;
    }

    public function itunesAuthor(): string
    {
        return $this->itunesAuthor;
    }

    public function itunesExplicit(): bool
    {
        return $this->itunesExplicit;
    }
}
