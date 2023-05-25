<?php

namespace Pumukit\PodcastBundle\Tests\Controller;

use Pumukit\CoreBundle\Tests\PumukitTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class DefaultControllerTest extends PumukitTestCase
{
    private $client;
    private $router;
    private $factory;
    private $skipTests = false;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        gc_collect_cycles();
    }

    public function testVideo()
    {
        $route = $this->router->generate('pumukit_podcast_video', []);
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        // $this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        // $this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        // $this->assertCount(1, $crawler->filter('itunes:category'));
        // $this->assertCount(1, $crawler->filter('itunes:summary'));
        // $this->assertCount(1, $crawler->filter('itunes:subtitle'));
        // $this->assertCount(1, $crawler->filter('itunes:author'));
        // $this->assertCount(1, $crawler->filter('itunes:owner'));
        // $this->assertCount(1, $crawler->filter('itunes:name'));
        // $this->assertCount(1, $crawler->filter('itunes:email'));
        // $this->assertCount(1, $crawler->filter('itunes:explicit'));
    }

    public function testAudio()
    {
        $route = $this->router->generate('pumukit_podcast_audio', []);
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        // $this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        // $this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        // $this->assertCount(1, $crawler->filter('itunes:category'));
        // $this->assertCount(1, $crawler->filter('itunes:summary'));
        // $this->assertCount(1, $crawler->filter('itunes:subtitle'));
        // $this->assertCount(1, $crawler->filter('itunes:author'));
        // $this->assertCount(1, $crawler->filter('itunes:owner'));
        // $this->assertCount(1, $crawler->filter('itunes:name'));
        // $this->assertCount(1, $crawler->filter('itunes:email'));
        // $this->assertCount(1, $crawler->filter('itunes:explicit'));
    }

    public function testSeriesVideo()
    {
        $series = $this->factory->createSeries();
        $route = $this->router->generate('pumukit_podcast_series_video', ['id' => $series->getId()]);
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        // $this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        // $this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        // $this->assertCount(1, $crawler->filter('itunes:category'));
        // $this->assertCount(1, $crawler->filter('itunes:summary'));
        // $this->assertCount(1, $crawler->filter('itunes:subtitle'));
        // $this->assertCount(1, $crawler->filter('itunes:author'));
        // $this->assertCount(1, $crawler->filter('itunes:owner'));
        // $this->assertCount(1, $crawler->filter('itunes:name'));
        // $this->assertCount(1, $crawler->filter('itunes:email'));
        // $this->assertCount(1, $crawler->filter('itunes:explicit'));
    }

    public function testSeriesAudio()
    {
        $series = $this->factory->createSeries();
        $route = $this->router->generate('pumukit_podcast_series_audio', ['id' => $series->getId()]);
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        // $this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        // $this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        // $this->assertCount(1, $crawler->filter('itunes:category'));
        // $this->assertCount(1, $crawler->filter('itunes:summary'));
        // $this->assertCount(1, $crawler->filter('itunes:subtitle'));
        // $this->assertCount(1, $crawler->filter('itunes:author'));
        // $this->assertCount(1, $crawler->filter('itunes:owner'));
        // $this->assertCount(1, $crawler->filter('itunes:name'));
        // $this->assertCount(1, $crawler->filter('itunes:email'));
        // $this->assertCount(1, $crawler->filter('itunes:explicit'));
    }

    public function testSeriesCollection()
    {
        $series = $this->factory->createSeries();
        $route = $this->router->generate('pumukit_podcast_series_collection', ['id' => $series->getId()]);
        $crawler = $this->client->request('GET', $route);
        $response = $this->client->getResponse();

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->headers->contains('Content-Type', 'text/xml; charset=UTF-8'));

        $this->assertCount(1, $crawler->filter('channel'));
        $this->assertCount(2, $crawler->filter('title'));
        $this->assertCount(2, $crawler->filter('link'));
        $this->assertCount(1, $crawler->filter('description'));
        $this->assertCount(1, $crawler->filter('generator'));
        // $this->assertCount(1, $crawler->filter('lastBuildDate'));
        $this->assertCount(1, $crawler->filter('language'));
        $this->assertCount(1, $crawler->filter('copyright'));
        // $this->assertCount(1, $crawler->filter('itunes:image'));
        $this->assertCount(1, $crawler->filter('image'));
        $this->assertCount(2, $crawler->filter('link'));
        // $this->assertCount(1, $crawler->filter('itunes:category'));
        // $this->assertCount(1, $crawler->filter('itunes:summary'));
        // $this->assertCount(1, $crawler->filter('itunes:subtitle'));
        // $this->assertCount(1, $crawler->filter('itunes:author'));
        // $this->assertCount(1, $crawler->filter('itunes:owner'));
        // $this->assertCount(1, $crawler->filter('itunes:name'));
        // $this->assertCount(1, $crawler->filter('itunes:email'));
        // $this->assertCount(1, $crawler->filter('itunes:explicit'));
    }
}
