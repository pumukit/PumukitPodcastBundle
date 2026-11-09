<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Services;

use Pumukit\PodcastBundle\PumukitPodcastBundle;

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
        private readonly int $feedLimit = 100,
        private readonly int $seriesFeedLimit = 500,
        private readonly int $cacheTtl = 1800,
        private readonly ?string $image = null,
        private readonly string $language = 'en',
        private readonly string $tagFeedsChannel = 'PUCHPODCAST',
        private readonly array $tagFeedsGroups = [],
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

    public function feedLimit(): int
    {
        return $this->feedLimit;
    }

    public function seriesFeedLimit(): int
    {
        return $this->seriesFeedLimit;
    }

    public function cacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function image(): ?string
    {
        return $this->image;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function tagFeedsChannel(): string
    {
        return $this->tagFeedsChannel;
    }

    public function tagFeedsGroup(string $name): ?array
    {
        return $this->tagFeedsGroups[$name] ?? null;
    }

    public function channels(): array
    {
        return array_values(array_unique([PumukitPodcastBundle::PODCAST_TAG, $this->tagFeedsChannel]));
    }
}
