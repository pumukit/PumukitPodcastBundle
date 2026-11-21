<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Controller;

use Pumukit\BasePlayerBundle\Services\TrackDeliveryService;
use Pumukit\PodcastBundle\Services\EpisodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    public function __construct(
        private readonly EpisodeRepository $episodes,
        private readonly TrackDeliveryService $trackDeliveryService,
    ) {}

    #[Route('/podcast/media/{id}.{ext}', name: 'pumukit_podcast_media', requirements: ['id' => '[a-f\d]{24}', 'ext' => '[A-Za-z0-9]{1,5}'], methods: ['GET', 'HEAD'])]
    public function mediaAction(string $id): Response
    {
        $episode = $this->episodes->findTrack($id);
        if (null === $episode) {
            throw $this->createNotFoundException();
        }

        return $this->trackDeliveryService->response($episode[1], false);
    }
}
