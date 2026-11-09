<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Controller;

use Pumukit\NewAdminBundle\Controller\NewAdminControllerInterface;
use Pumukit\PodcastBundle\PumukitPodcastBundle;
use Pumukit\PodcastBundle\Services\EpisodeType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ACCESS_MULTIMEDIA_SERIES')]
class ModalController extends AbstractController implements NewAdminControllerInterface
{
    #[Route('/admin/podcast/model/mm/{id}', name: 'pumukitpodcast_modal_index', methods: ['GET'])]
    public function indexAction(MultimediaObject $multimediaObject): Response
    {
        return $this->render('@PumukitPodcast/Modal/index.html.twig', [
            'mm' => $multimediaObject,
            'published' => $multimediaObject->containsTagWithCod(PumukitPodcastBundle::PODCAST_TAG),
            'hasVideo' => null !== EpisodeType::VIDEO->track($multimediaObject),
            'hasAudio' => null !== EpisodeType::AUDIO->track($multimediaObject),
        ]);
    }
}
