<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use MongoDB\BSON\ObjectId;
use Pumukit\PodcastBundle\PumukitPodcastBundle;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;

final class EpisodeRepository
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly ConfigurationService $configuration,
    ) {}

    public function latest(EpisodeType $type): array
    {
        return $this->episodes(PumukitPodcastBundle::PODCAST_TAG, $type)
            ->limit($this->configuration->feedLimit())
            ->getQuery()->execute()->toArray()
        ;
    }

    public function bySeries(Series $series, EpisodeType $type): array
    {
        return $this->episodes(PumukitPodcastBundle::PODCAST_TAG, $type)
            ->field('series')->references($series)
            ->limit($this->configuration->seriesFeedLimit())
            ->getQuery()->execute()->toArray()
        ;
    }

    public function byTags(array $codes): array
    {
        $queryBuilder = $this->episodes($this->configuration->tagFeedsChannel(), EpisodeType::AUDIO);
        $queryBuilder->addAnd($queryBuilder->expr()->field('tags.cod')->in(array_values($codes)));

        return $queryBuilder
            ->limit($this->configuration->feedLimit())
            ->getQuery()->execute()->toArray()
        ;
    }

    public function seriesIds(): array
    {
        $hidden = $this->documentManager->createQueryBuilder(Series::class)
            ->field('hide')->equals(true)
            ->distinct('_id')
            ->getQuery()->execute()
        ;
        $hidden = array_map(static fn ($id): string => (string) $id, is_array($hidden) ? $hidden : iterator_to_array($hidden, false));

        $ids = $this->episodes(PumukitPodcastBundle::PODCAST_TAG, EpisodeType::ALL)
            ->distinct('series')
            ->getQuery()->execute()
        ;
        $ids = array_map(static fn ($id): string => (string) $id, is_array($ids) ? $ids : iterator_to_array($ids, false));

        return array_values(array_diff($ids, $hidden));
    }

    public function tagExists(string $code): bool
    {
        return null !== $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $code]);
    }

    public function findTrack(string $trackId): ?array
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $trackId)) {
            return null;
        }

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
            ->field('status')->equals(MultimediaObject::STATUS_PUBLISHED)
            ->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC)
            ->field('tags.cod')->in($this->configuration->channels())
            ->field('tracks')->elemMatch(['_id' => new ObjectId($trackId)] + EpisodeType::ALL->trackCriteria())
            ->getQuery()->getSingleResult()
        ;
        if (!$multimediaObject instanceof MultimediaObject) {
            return null;
        }

        $track = $multimediaObject->getTrackById($trackId);
        if (!$track instanceof Track) {
            return null;
        }

        return [$multimediaObject, $track];
    }

    private function episodes(string $channel, EpisodeType $type): Builder
    {
        $queryBuilder = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
            ->field('status')->equals(MultimediaObject::STATUS_PUBLISHED)
            ->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC)
            ->field('tracks')->elemMatch($type->trackCriteria())
            ->sort('public_date', -1)
        ;
        $queryBuilder->addAnd($queryBuilder->expr()->field('tags.cod')->equals($channel));

        return $queryBuilder;
    }
}
