<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Services;

use Pumukit\SchemaBundle\Document\Exception\PathException;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\PicService;
use Pumukit\WebTVBundle\PumukitWebTVBundle;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class FeedService
{
    private const SERIES_ROUTES = [
        'video' => 'pumukit_podcast_series_video',
        'audio' => 'pumukit_podcast_series_audio',
        'all' => 'pumukit_podcast_series_collection',
    ];

    public function __construct(
        private readonly EpisodeRepository $episodes,
        private readonly FeedWriter $writer,
        private readonly ConfigurationService $configuration,
        private readonly UrlGeneratorInterface $router,
        private readonly PicService $picService,
        private readonly CacheInterface $cache,
        private readonly array $pumukitInfo,
    ) {}

    public function latest(EpisodeType $type, string $baseUrl): Feed
    {
        return $this->cached($type->value, $baseUrl, fn (): string => $this->rss(
            $this->channel($baseUrl, $this->router->generate('pumukit_podcast_'.$type->value, [], UrlGeneratorInterface::ABSOLUTE_URL)),
            $this->episodes->latest($type),
            $type,
            $baseUrl,
        ));
    }

    public function series(Series $series, EpisodeType $type, string $baseUrl): Feed
    {
        return $this->cached('series_'.$series->getId().'_'.$type->value, $baseUrl, fn (): string => $this->rss(
            $this->channel($baseUrl, $this->router->generate(self::SERIES_ROUTES[$type->value], ['id' => $series->getId()], UrlGeneratorInterface::ABSOLUTE_URL), $series),
            $this->episodes->bySeries($series, $type),
            $type,
            $baseUrl,
        ));
    }

    public function tag(string $tag, string $baseUrl): ?Feed
    {
        $codes = $this->configuration->tagFeedsGroup($tag);
        if (null === $codes) {
            if (!$this->episodes->tagExists($tag)) {
                return null;
            }
            $codes = [$tag];
        }

        return $this->cached('tag_'.$tag, $baseUrl, fn (): string => $this->rss(
            $this->channel($baseUrl, $this->router->generate('pumukit_podcast_audio_bytag', ['tag' => $tag], UrlGeneratorInterface::ABSOLUTE_URL)),
            $this->episodes->byTags($codes),
            EpisodeType::AUDIO,
            $baseUrl,
        ));
    }

    public function list(string $baseUrl): Feed
    {
        return $this->cached('list', $baseUrl, fn (): string => $this->writer->list(array_map(
            fn (string $id): string => $this->router->generate('pumukit_podcast_series_collection', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->episodes->seriesIds(),
        )));
    }

    private function cached(string $key, string $baseUrl, \Closure $build): Feed
    {
        $ttl = $this->configuration->cacheTtl();
        if (0 === $ttl) {
            return new Feed($build(), time(), 0);
        }

        $feed = $this->cache->get('pumukit_podcast_'.hash('xxh128', $baseUrl.'|'.$key), static function (ItemInterface $item) use ($ttl, $build): array {
            $item->expiresAfter($ttl);

            return ['xml' => $build(), 'builtAt' => time()];
        });

        return new Feed($feed['xml'], $feed['builtAt'], max(0, $feed['builtAt'] + $ttl - time()));
    }

    private function channel(string $baseUrl, string $self, ?Series $series = null): array
    {
        $language = $this->configuration->language();
        $description = $series ? $series->getDescription($language) : ($this->configuration->channelDescription() ?: (string) ($this->pumukitInfo['description'] ?? ''));

        return [
            'self' => $self,
            'title' => $series ? $series->getTitle($language) : ($this->configuration->channelTitle() ?: (string) ($this->pumukitInfo['title'] ?? '')),
            'link' => $baseUrl.'/',
            'description' => $description,
            'lastBuildDate' => (new \DateTimeImmutable())->format(\DATE_RSS),
            'language' => $language,
            'copyright' => ($series?->getProperty('copyright') ?: null) ?? $this->configuration->channelCopyright(),
            'image' => $this->absolute($baseUrl, $this->configuration->image() ?? (string) ($this->pumukitInfo['logo'] ?? '')),
            'category' => ($series?->getProperty('itunescategory') ?: null) ?? $this->configuration->itunesCategory(),
            'summary' => $series ? $description : ($this->configuration->itunesSummary() ?: $description),
            'subtitle' => ($series?->getSubtitle($language) ?: null) ?? ($this->configuration->itunesSubtitle() ?: $description),
            'author' => $this->configuration->itunesAuthor(),
            'email' => (string) ($this->pumukitInfo['email'] ?? ''),
            'explicit' => $this->configuration->itunesExplicit() ? 'true' : 'false',
        ];
    }

    private function rss(array $channel, array $multimediaObjects, EpisodeType $type, string $baseUrl): string
    {
        $items = [];
        foreach ($multimediaObjects as $multimediaObject) {
            $track = $type->track($multimediaObject);
            if (!$track instanceof Track) {
                continue;
            }

            try {
                $storage = $track->storage();
            } catch (PathException) {
                continue;
            }
            $items[] = $this->item($multimediaObject, $track, $storage, $channel, $baseUrl);
        }

        return $this->writer->rss($channel, $items);
    }

    private function item(MultimediaObject $multimediaObject, Track $track, Storage $storage, array $channel, string $baseUrl): array
    {
        $language = $this->configuration->language();
        $isAudio = $track->tags()->containsTag(EpisodeType::AUDIO_TAG);
        $extension = $this->extension($storage) ?: ($isAudio ? 'mp3' : 'mp4');
        $url = $this->router->generate('pumukit_podcast_media', ['id' => $track->id(), 'ext' => $extension], UrlGeneratorInterface::ABSOLUTE_URL);
        $storageUrl = $storage->url()?->url();
        $date = $multimediaObject->getPublicDate() ?? $multimediaObject->getRecordDate();

        return [
            'title' => $multimediaObject->getTitle($language) ?: $multimediaObject->getSeriesTitle($language),
            'subtitle' => $multimediaObject->getSubtitle($language),
            'description' => $multimediaObject->getDescription($language),
            'link' => $multimediaObject->containsTagWithCod(PumukitWebTVBundle::WEB_TV_TAG)
                ? $this->router->generate('pumukit_webtv_multimediaobject_index', ['id' => $multimediaObject->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                : null,
            'url' => $url,
            'length' => $track->metadata()->size(),
            'type' => MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? ($isAudio ? 'audio/mpeg' : 'video/mp4'),
            'guid' => $storageUrl ? $this->absolute($baseUrl, $storageUrl) : $url,
            'duration' => $multimediaObject->getDuration() ?: $track->getDuration(),
            'author' => '' !== $channel['email'] ? $channel['email'].' ('.$channel['title'].')' : null,
            'itunesAuthor' => $multimediaObject->getCopyright(),
            'keywords' => $multimediaObject->getKeywordsAsString($language),
            'explicit' => $channel['explicit'],
            'image' => $this->absolute($baseUrl, (string) $this->picService->getFirstUrlPic($multimediaObject)),
            'pubDate' => $date instanceof \DateTimeInterface ? $date->format(\DATE_RSS) : null,
        ];
    }

    private function extension(Storage $storage): string
    {
        foreach ([parse_url((string) $storage->url()?->url(), PHP_URL_PATH), $storage->path()?->path()] as $candidate) {
            $extension = strtolower(pathinfo((string) $candidate, PATHINFO_EXTENSION));
            if (preg_match('/^[a-z0-9]{1,5}$/', $extension)) {
                return $extension;
            }
        }

        return '';
    }

    private function absolute(string $baseUrl, string $url): string
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $baseUrl.$url;
        }

        return $url;
    }
}
