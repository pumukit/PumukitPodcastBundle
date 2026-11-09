<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Controller;

use Pumukit\PodcastBundle\Services\EpisodeType;
use Pumukit\PodcastBundle\Services\Feed;
use Pumukit\PodcastBundle\Services\FeedService;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/podcast', defaults: ['_format' => 'xml'], methods: ['GET', 'HEAD'])]
class FeedController extends AbstractController
{
    private const SERIES_ID = '[a-f\d]{24}';

    public function __construct(
        private readonly FeedService $feedService,
    ) {}

    #[Route('/list.xml', name: 'pumukit_podcast_list')]
    public function listAction(Request $request): Response
    {
        return $this->respond($request, $this->feedService->list($this->baseUrl($request)));
    }

    #[Route('/video.xml', name: 'pumukit_podcast_video')]
    public function videoAction(Request $request): Response
    {
        return $this->respond($request, $this->feedService->latest(EpisodeType::VIDEO, $this->baseUrl($request)));
    }

    #[Route('/audio.xml', name: 'pumukit_podcast_audio')]
    public function audioAction(Request $request): Response
    {
        return $this->respond($request, $this->feedService->latest(EpisodeType::AUDIO, $this->baseUrl($request)));
    }

    #[Route('/series/{id}/video.xml', name: 'pumukit_podcast_series_video', requirements: ['id' => self::SERIES_ID])]
    public function seriesVideoAction(Series $series, Request $request): Response
    {
        return $this->series($request, $series, EpisodeType::VIDEO);
    }

    #[Route('/series/{id}/audio.xml', name: 'pumukit_podcast_series_audio', requirements: ['id' => self::SERIES_ID])]
    public function seriesAudioAction(Series $series, Request $request): Response
    {
        return $this->series($request, $series, EpisodeType::AUDIO);
    }

    #[Route('/series/{id}/collection.xml', name: 'pumukit_podcast_series_collection', requirements: ['id' => self::SERIES_ID])]
    public function seriesCollectionAction(Series $series, Request $request): Response
    {
        return $this->series($request, $series, EpisodeType::ALL);
    }

    #[Route('/{tag}/audio.xml', name: 'pumukit_podcast_audio_bytag')]
    public function getAudiosByTagAction(string $tag, Request $request): Response
    {
        $feed = $this->feedService->tag($tag, $this->baseUrl($request));
        if (!$feed instanceof Feed) {
            throw $this->createNotFoundException();
        }

        return $this->respond($request, $feed);
    }

    private function series(Request $request, Series $series, EpisodeType $type): Response
    {
        if ($series->isHide()) {
            throw $this->createNotFoundException();
        }

        return $this->respond($request, $this->feedService->series($series, $type, $this->baseUrl($request)));
    }

    private function respond(Request $request, Feed $feed): Response
    {
        $response = new Response($feed->xml, Response::HTTP_OK, ['Content-Type' => 'text/xml; charset=UTF-8']);
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        $response->setPublic();
        $response->setMaxAge($feed->maxAge);
        $response->setEtag($feed->etag());
        $response->setLastModified((new \DateTimeImmutable())->setTimestamp($feed->builtAt));
        $response->isNotModified($request);

        return $response;
    }

    private function baseUrl(Request $request): string
    {
        return $request->getSchemeAndHttpHost().$request->getBasePath();
    }
}
