<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Pumukit\PodcastBundle\Command\PodcastInitTagsCommand;
use Pumukit\PodcastBundle\Controller\FeedController;
use Pumukit\PodcastBundle\DependencyInjection\PumukitPodcastExtension;
use Pumukit\PodcastBundle\Routing\RouteLoader;
use Pumukit\PodcastBundle\Services\ConfigurationService;
use Pumukit\PodcastBundle\Services\FeedService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 *
 * @coversNothing
 */
class PumukitPodcastExtensionTest extends TestCase
{
    public function testTheDisabledBundleOnlyRegistersTheRouteLoader(): void
    {
        $container = $this->load(['enable' => false]);

        $this->assertSame([RouteLoader::class], $this->podcastServices($container));
        $this->assertSame('%pumukit_podcast.enable%', $container->getDefinition(RouteLoader::class)->getArgument('$enabled'));
        $this->assertTrue($container->getDefinition(RouteLoader::class)->hasTag('routing.loader'));
    }

    public function testTheEnabledBundleRegistersItsServices(): void
    {
        $container = $this->load([]);

        foreach ([FeedController::class, PodcastInitTagsCommand::class, ConfigurationService::class, FeedService::class, RouteLoader::class] as $id) {
            $this->assertTrue($container->hasDefinition($id), $id);
        }
    }

    public function testTheCachePoolIsOnlyPrependedWhenTheBundleIsEnabled(): void
    {
        $this->assertSame([], $this->prepend(['enable' => false])->getExtensionConfig('framework'));
        $this->assertSame(
            [['cache' => ['pools' => [PumukitPodcastExtension::CACHE_POOL => ['adapter' => 'cache.app']]]]],
            $this->prepend([])->getExtensionConfig('framework'),
        );
    }

    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        new PumukitPodcastExtension()->load([$config], $container);

        return $container;
    }

    private function prepend(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->prependExtensionConfig('pumukit_podcast', $config);
        new PumukitPodcastExtension()->prepend($container);

        return $container;
    }

    private function podcastServices(ContainerBuilder $container): array
    {
        return array_values(array_filter(array_keys($container->getDefinitions()), static fn (string $id): bool => str_starts_with($id, 'Pumukit\PodcastBundle\\')));
    }
}
