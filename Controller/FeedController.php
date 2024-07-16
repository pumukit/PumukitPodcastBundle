<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\PodcastBundle\Services\ConfigurationService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @Route("/podcast")
 */
class FeedController extends AbstractController
{
    public const ITUNES_DTD_URL = 'https://www.itunes.com/dtds/podcast-1.0.dtd';
    public const ITUNESU_FEED_URL = 'https://www.itunesu.com/feed';
    public const ATOM_URL = 'https://www.w3.org/2005/Atom';

    private $configurationService;
    private $documentManager;
    private $router;
    private $context;
    private $pumukitInfo;

    public function __construct(
        ConfigurationService $configurationService,
        DocumentManager $documentManager,
        UrlGeneratorInterface $router,
        RequestContext $context,
        $pumukitInfo
    ) {
        $this->configurationService = $configurationService;
        $this->documentManager = $documentManager;
        $this->router = $router;
        $this->context = $context;
        $this->pumukitInfo = $pumukitInfo;
    }

    /**
     * @Route("/list.xml", defaults={"_format": "xml"}, name="pumukit_podcast_list")
     */
    public function listAction(): Response
    {
        $qb = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
        $qb->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        $series = $qb->distinct('series')->getQuery()->execute();

        $xml = new \SimpleXMLElement('<list/>');
        foreach ($series as $s) {
            $url = $this->router->generate('pumukit_podcast_series_collection', ['id' => $s], UrlGeneratorInterface::ABSOLUTE_URL);
            $xml->addChild('podcast', $url);
        }

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/video.xml", defaults={"_format": "xml"}, name="pumukit_podcast_video")
     */
    public function videoAction(Request $request): Response
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudio(false);
        $values = $this->getValues($request, 'video', null);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'video');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/audio.xml", defaults={"_format": "xml"}, name="pumukit_podcast_audio")
     */
    public function audioAction(Request $request): Response
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudio(true);
        $values = $this->getValues($request, 'audio', null);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'audio');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/series/{id}/video.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_video")
     */
    public function seriesVideoAction(Series $series, Request $request): Response
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudioAndSeries(false, $series);
        $values = $this->getValues($request, 'video', $series);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'video');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/series/{id}/audio.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_audio")
     */
    public function seriesAudioAction(Series $series, Request $request): Response
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudioAndSeries(true, $series);
        $values = $this->getValues($request, 'audio', $series);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'audio');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/series/{id}/collection.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_collection")
     */
    public function seriesCollectionAction(Series $series, Request $request): Response
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsBySeries($series);
        $values = $this->getValues($request, 'video', $series);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'all');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/{tag}/audio.xml", defaults={"_format": "xml"}, name="pumukit_podcast_audio_bytag")
     */
    public function getAudiosByTagAction(string $tag, Request $request)
    {
        $multimediaObjects = $this->getPodcastAudiosByTag(true, $tag);
        $values = $this->getValues($request, 'audio');
        $xml = $this->getXMLElement($multimediaObjects, $values, 'audio');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    private function createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio = false)
    {
        $qb = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
        $qb->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        $qb->field('tracks')->elemMatch(
            $qb->expr()
                ->field('only_audio')->equals($isOnlyAudio)
                ->field('tags')->all(['podcast'])
        );

        return $qb;
    }

    private function createPodcastAudioByTagQueryBuilder($isOnlyAudio = false)
    {
        $qb = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
        $qb->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        $qb->field('status')->equals(MultimediaObject::STATUS_PUBLISHED);
        $qb->field('tracks')->elemMatch(
            $qb->expr()
                ->field('only_audio')->equals($isOnlyAudio)
        );

        return $qb;
    }

    private function getPodcastMultimediaObjectsByAudio($isOnlyAudio = false)
    {
        $qb = $this->createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio);

        return $qb->getQuery()->execute();
    }

    private function getPodcastMultimediaObjectsByAudioAndSeries($isOnlyAudio, Series $series)
    {
        $qb = $this->createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio);
        $qb->field('series')->references($series);

        return $qb->getQuery()->execute();
    }

    private function getPodcastAudiosByTag($isOnlyAudio, string $tag)
    {
        $qb = $this->createPodcastAudioByTagQueryBuilder($isOnlyAudio);
        $qb->field('tags.cod')->equals($tag);

        return $qb->getQuery()->execute();
    }

    private function getPodcastMultimediaObjectsBySeries(Series $series)
    {
        $qb = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
        $qb->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        $qb->field('series')->references($series);

        return $qb->getQuery()->execute();
    }

    private function getValues(Request $request, $audioVideoType = 'video', $series = null)
    {
        $pumukitInfo = $this->pumukitInfo;

        $values = [];
        $values['base_url'] = $this->getBaseUrl().$request->getBasePath();
        $values['requestURI'] = $values['base_url'].$request->getRequestUri();
        $values['image_url'] = $values['base_url'].'/bundles/pumukitpodcast/images/gc_'.$audioVideoType.'.jpg';
        $values['language'] = $request->getLocale();
        $values['itunes_author'] = $this->configurationService->itunesAuthor();
        $values['email'] = $pumukitInfo['email'];
        $values['itunes_explicit'] = $this->configurationService->itunesExplicit() ? 'yes' : 'no';

        if ($series) {
            $values['channel_title'] = $series->getTitle();
            $values['channel_description'] = $series->getDescription();
            $values['copyright'] = $series->getCopyright() ?: 'PuMuKIT2 2015';
            $values['itunes_category'] = $series->getProperty('itunescategory') ?: $this->configurationService->itunesCategory();
            $values['itunes_summary'] = $series->getDescription();
            $values['itunes_subtitle'] = $series->getSubtitle() ?: ($this->configurationService->itunesSubtitle() ?: $values['channel_description']);
        } else {
            $values['channel_title'] = $this->configurationService->channelTitle() ?: $pumukitInfo['title'];
            $values['channel_description'] = $this->configurationService->channelDescription() ?: $pumukitInfo['description'];
            $values['copyright'] = $this->configurationService->channelCopyright() ?: $pumukitInfo['copyright'] ?? 'PuMuKIT2 2015';
            $values['itunes_category'] = $this->configurationService->itunesCategory();
            $values['itunes_summary'] = $this->configurationService->itunesSummary() ?: $values['channel_description'];
            $values['itunes_subtitle'] = $this->configurationService->itunesSubtitle() ?: $values['channel_description'];
        }

        return $values;
    }

    private function getXMLElement($multimediaObjects, $values, $trackType = 'video')
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'
                                     .'<rss xmlns:itunes="'.self::ITUNES_DTD_URL
                                     .'" xmlns:itunesu="'.self::ITUNESU_FEED_URL
                                     .'" xmlns:atom="'.self::ATOM_URL
                                     .'" xml:lang="en" version="2.0"></rss>'
        );
        $channel = $xml->addChild('channel');
        $atomLink = $channel->addChild('atom:link', null, self::ATOM_URL);
        $atomLink->addAttribute('href', $values['requestURI']);
        $atomLink->addAttribute('rel', 'self');
        $atomLink->addAttribute('type', 'application/rss+xml');
        $channel->addChild('title', htmlspecialchars($values['channel_title']));
        $channel->addChild('link', $values['base_url']);
        $channel->addChild('description', htmlspecialchars($values['channel_description']));
        $channel->addChild('generator', 'PuMuKiT');
        $channel->addChild('lastBuildDate', (new \DateTime('now'))->format('r'));
        $channel->addChild('language', $values['language']);
        $channel->addChild('copyright', $values['copyright']);

        $itunesImage = $channel->addChild('itunes:image', null, self::ITUNES_DTD_URL);
        $itunesImage->addAttribute('href', $values['image_url']);

        $image = $channel->addChild('image');
        $image->addChild('url', $values['image_url']);
        $image->addChild('title', htmlspecialchars($values['channel_title']));
        $image->addChild('link', $values['base_url']);

        $itunesCategory = $channel->addChild('itunes:category', null, self::ITUNES_DTD_URL);
        $itunesCategory->addAttribute('text', $values['itunes_category']);

        $channel->addChild('itunes:summary', htmlspecialchars($values['itunes_summary']), self::ITUNES_DTD_URL);
        $channel->addChild('itunes:subtitle', htmlspecialchars($values['itunes_subtitle']), self::ITUNES_DTD_URL);
        $channel->addChild('itunes:author', htmlspecialchars($values['itunes_author']), self::ITUNES_DTD_URL);

        $itunesOwner = $channel->addChild('itunes:owner', null, self::ITUNES_DTD_URL);
        $itunesOwner->addChild('itunes:name', $values['itunes_author'], self::ITUNES_DTD_URL);
        $itunesOwner->addChild('itunes:email', $values['email'], self::ITUNES_DTD_URL);

        $channel->addChild('itunes:explicit', $values['itunes_explicit'], self::ITUNES_DTD_URL);

        $this->completeTracksInfo($channel, $multimediaObjects, $values, $trackType);

        return $xml;
    }

    private function completeTracksInfo($channel, $multimediaObjects, $values, $trackType = 'video')
    {
        $tagRepo = $this->documentManager->getRepository(Tag::class);
        $itunesUTag = $tagRepo->findOneBy(['cod' => 'ITUNESU']);

        foreach ($multimediaObjects as $multimediaObject) {
            $track = $this->getPodcastTrack($multimediaObject, $trackType);
            if ($track) {
                $item = $channel->addChild('item');

                $title = (0 === strlen($multimediaObject->getTitle())) ?
                  $multimediaObject->getSeries()->getTitle() :
                  $multimediaObject->getTitle();
                $item->addChild('title', htmlspecialchars($title));
                $item->addChild('itunes:subtitle', htmlspecialchars($multimediaObject->getSubtitle()), self::ITUNES_DTD_URL);
                $item->addChild('itunes:summary', htmlspecialchars($multimediaObject->getDescription()), self::ITUNES_DTD_URL);
                $item->addChild('description', htmlspecialchars($multimediaObject->getDescription()));

                if (null !== $itunesUTag) {
                    foreach ($multimediaObject->getTags() as $tag) {
                        if ($tag->isDescendantOf($itunesUTag)) {
                            $itunesUCategory = $item->addChild('itunesu:category', null, self::ITUNESU_FEED_URL);
                            $itunesUCategory->addAttribute('itunesu:code', $tag->getCod(), self::ITUNESU_FEED_URL);
                        }
                    }
                }

                if ($multimediaObject->isPublished() && $multimediaObject->containsTagWithCod('PUCHWEBTV')) {
                    $link = $this->router->generate('pumukit_webtv_multimediaobject_index', ['id' => $multimediaObject->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $item->addChild('link', $link);
                }

                $enclosure = $item->addChild('enclosure');
                $enclosure->addAttribute('url', $this->getAbsoluteUrl($track->getUrl()));
                $enclosure->addAttribute('length', (string) $track->getSize());
                $enclosure->addAttribute('type', $track->getMimeType());

                $item->addChild('guid', $this->getAbsoluteUrl($track->getUrl()));
                $item->addChild('itunes:duration', $this->getDurationString($multimediaObject), self::ITUNES_DTD_URL);
                $item->addChild('author', $values['email'].' ('.$values['channel_title'].')');
                $item->addChild('itunes:author', $multimediaObject->getCopyright(), self::ITUNES_DTD_URL);
                $item->addChild('itunes:keywords', htmlspecialchars($multimediaObject->getKeywordsAsString()), self::ITUNES_DTD_URL);
                $item->addChild('itunes:explicit', $values['itunes_explicit'], self::ITUNES_DTD_URL);
                $item->addChild('itunes:image', $this->getAbsoluteUrl($multimediaObject->getFirstUrlPic()), self::ITUNES_DTD_URL);
                $item->addChild('pubDate', $multimediaObject->getRecordDate()->format('r'));
            }
            $this->documentManager->clear();
        }

        return $channel;
    }

    private function getPodcastTrack(MultimediaObject $multimediaObject, $trackType = 'video')
    {
        if ('all' === $trackType) {
            $track = $this->getVideoTrack($multimediaObject);
            if (null === $track) {
                $track = $this->getAudioTrack($multimediaObject);
            }
        } elseif ('video' === $trackType) {
            $track = $this->getVideoTrack($multimediaObject);
        } else {
            $track = $this->getAudioTrack($multimediaObject);
        }

        return $track;
    }

    private function getVideoTrack(MultimediaObject $multimediaObject)
    {
        $video_all_tags = ['podcast'];
        $video_not_all_tags = ['audio'];

        return $multimediaObject->getFilteredTrackWithTags(
            [],
            $video_all_tags,
            [],
            $video_not_all_tags,
            false
        );
    }

    private function getAudioTrack(MultimediaObject $multimediaObject)
    {
        $audio_all_tags = ['podcast', 'audio'];
        $audio_not_all_tags = [];

        return $multimediaObject->getFilteredTrackWithTags(
            [],
            $audio_all_tags,
            [],
            $audio_not_all_tags,
            false
        );
    }

    private function getDurationString(MultimediaObject $multimediaObject)
    {
        $minutes = floor($multimediaObject->getDuration() / 60);
        $seconds = $multimediaObject->getDuration() % 60;

        return $minutes.':'.$seconds;
    }

    private function getAbsoluteUrl($url = '')
    {
        if ($url && '/' == $url[0]) {
            return $this->getBaseUrl().$url;
        }

        return $url;
    }

    private function getBaseUrl()
    {
        $context = $this->context;
        if (!$context) {
            throw new \RuntimeException('To generate an absolute URL for an asset, the Symfony Routing component is required.');
        }

        $scheme = $context->getScheme();
        $host = $context->getHost();
        $port = '';
        if ('http' === $scheme && 80 != $context->getHttpPort()) {
            $port = ':'.$context->getHttpPort();
        } elseif ('https' === $scheme && 443 != $context->getHttpsPort()) {
            $port = ':'.$context->getHttpsPort();
        }

        return $scheme.'://'.$host.$port;
    }
}
