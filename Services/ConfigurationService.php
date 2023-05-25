<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Services;

class ConfigurationService
{
    private $channelTitle;
    private $channelDescription;
    private $channelCopyright;
    private $itunesCategory;
    private $itunesSummary;
    private $itunesSubtitle;
    private $itunesAuthor;
    private $itunesExplicit;

    public function __construct(
        string $channelTitle,
        string $channelDescription,
        string $channelCopyright,
        string $itunesCategory,
        string $itunesSummary,
        string $itunesSubtitle,
        string $itunesAuthor,
        bool $itunesExplicit
    ) {
        $this->channelTitle = $channelTitle;
        $this->channelDescription = $channelDescription;
        $this->channelCopyright = $channelCopyright;
        $this->itunesCategory = $itunesCategory;
        $this->itunesSummary = $itunesSummary;
        $this->itunesSubtitle = $itunesSubtitle;
        $this->itunesAuthor = $itunesAuthor;
        $this->itunesExplicit = $itunesExplicit;
    }

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
