<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Pumukit\PodcastBundle\Routing\RouteLoader;

/**
 * @internal
 *
 * @coversNothing
 */
class RouteLoaderTest extends TestCase
{
    public function testNoRouteIsLoadedWhenItIsDisabled(): void
    {
        $this->assertCount(0, new RouteLoader(false)->load('@PumukitPodcastBundle/Controller/', RouteLoader::TYPE));
    }

    public function testItOnlySupportsItsType(): void
    {
        $loader = new RouteLoader(true);

        $this->assertTrue($loader->supports('@PumukitPodcastBundle/Controller/', RouteLoader::TYPE));
        $this->assertFalse($loader->supports('@PumukitPodcastBundle/Controller/', 'attribute'));
    }
}
