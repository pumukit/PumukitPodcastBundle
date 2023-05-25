<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Controller;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModalController extends AbstractController
{
    /**
     * @Route("/admin/podcast/model/mm/{id}", name="pumukitpodcast_modal_index", defaults={"filter": false})
     */
    public function indexAction(MultimediaObject $mm): Response
    {
        return $this->render('@PumukitPodcast/Modal/index.html.twig', ['mm' => $mm]);
    }
}
