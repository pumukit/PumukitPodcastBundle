<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Controller;

use Pumukit\BasePlayerBundle\Services\TrackUrlService;
use Pumukit\PodcastBundle\Services\EpisodeRepository;
use Pumukit\SchemaBundle\Document\Exception\PathException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    public function __construct(
        private readonly EpisodeRepository $episodes,
        private readonly TrackUrlService $trackUrlService,
    ) {}

    #[Route('/podcast/media/{id}.{ext}', name: 'pumukit_podcast_media', requirements: ['id' => '[a-f\d]{24}', 'ext' => '[A-Za-z0-9]{1,5}'], methods: ['GET', 'HEAD'])]
    public function mediaAction(string $id): Response
    {
        $episode = $this->episodes->findTrack($id);
        if (null === $episode) {
            throw $this->createNotFoundException();
        }

        try {
            $storage = $episode[1]->storage();
        } catch (PathException) {
            throw $this->createNotFoundException();
        }
        if (!$storage->isLocalStorageSystem()) {
            $url = $storage->url()?->url();
            if (!$url) {
                throw $this->createNotFoundException();
            }

            return new RedirectResponse($this->trackUrlService->signForDownloadServer($url));
        }

        $path = $storage->path()?->path();
        if (!$path || !is_file($path)) {
            throw $this->createNotFoundException();
        }

        BinaryFileResponse::trustXSendfileTypeHeader();
        $response = new BinaryFileResponse($path, Response::HTTP_OK, [], true, ResponseHeaderBag::DISPOSITION_INLINE);
        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }
}
