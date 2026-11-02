<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Services;

use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;

enum EpisodeType: string
{
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case ALL = 'all';

    public const TRACK_TAG = 'podcast';
    public const AUDIO_TAG = 'audio';

    public function trackCriteria(): array
    {
        return match ($this) {
            self::VIDEO => ['tags' => ['$all' => [self::TRACK_TAG], '$ne' => self::AUDIO_TAG], 'hide' => false],
            self::AUDIO => ['tags' => ['$all' => [self::TRACK_TAG, self::AUDIO_TAG]], 'hide' => false],
            self::ALL => ['tags' => self::TRACK_TAG, 'hide' => false],
        };
    }

    public function track(MultimediaObject $multimediaObject): ?Track
    {
        $video = null;
        $audio = null;
        foreach ($multimediaObject->tracks() as $track) {
            if ($track->isHide() || !$track->tags()->containsTag(self::TRACK_TAG)) {
                continue;
            }
            if ($track->tags()->containsTag(self::AUDIO_TAG)) {
                $audio ??= $track;
            } else {
                $video ??= $track;
            }
        }

        return match ($this) {
            self::VIDEO => $video,
            self::AUDIO => $audio,
            self::ALL => $video ?? $audio,
        };
    }
}
