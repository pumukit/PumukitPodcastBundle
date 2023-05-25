<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\PodcastBundle\PumukitPodcastBundle;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FilterListener
{
    private $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $activateFilter = $this->activateFilter($event);

        if ($activateFilter) {
            $this->settingParametersFilter();
        }
    }

    private function activateFilter($event): bool
    {
        $req = $event->getRequest();
        $routeParams = $req->attributes->get('_route_params');

        $isMasterRequest = HttpKernelInterface::MAIN_REQUEST === $event->getRequestType();
        $isPodCastBundle = false !== strpos($req->attributes->get('_controller'), 'PodcastBundle');
        $isDefinedFilter = !isset($routeParams['filter']) || $routeParams['filter'];

        return $isMasterRequest && $isPodCastBundle && $isDefinedFilter;
    }

    private function settingParametersFilter(): void
    {
        $filter = $this->documentManager->getFilterCollection()->enable('frontend');

        $filter->setParameter('pub_channel_tag', PumukitPodcastBundle::PODCAST_TAG);
        $filter->setParameter('status', MultimediaObject::STATUS_PUBLISHED);
        $filter->setParameter('display_track_tag', 'podcast');
    }
}
