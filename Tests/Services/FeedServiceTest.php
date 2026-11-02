<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Tests\Services;

use Pumukit\PodcastBundle\Services\ConfigurationService;
use Pumukit\PodcastBundle\Services\EpisodeRepository;
use Pumukit\PodcastBundle\Services\EpisodeType;
use Pumukit\PodcastBundle\Services\FeedService;
use Pumukit\PodcastBundle\Services\FeedWriter;
use Pumukit\PodcastBundle\Tests\PodcastTestCase;
use Pumukit\SchemaBundle\Services\PicService;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class FeedServiceTest extends PodcastTestCase
{
    public function testTheFeedsAreLimitedToTheNewestEpisodes(): void
    {
        $this->episode('January', date: '2026-01-01');
        $this->episode('March', date: '2026-03-01');
        $this->episode('February', date: '2026-02-01');

        $this->assertSame(['March', 'February'], $this->titlesOf($this->service(feedLimit: 2)->latest(EpisodeType::AUDIO, 'https://tv.example.com')->xml));
        $this->assertSame(['March'], $this->titlesOf($this->service(seriesFeedLimit: 1)->series($this->series, EpisodeType::ALL, 'https://tv.example.com')->xml));
    }

    public function testATagGroupJoinsTheEpisodesOfItsTagsInItsChannel(): void
    {
        $this->episode('Health', tags: ['PUCHWEBTV', 'U310000'], date: '2026-01-01');
        $this->episode('Biology', tags: ['PUCHWEBTV', 'U240000'], date: '2026-02-01');
        $this->episode('Technology', tags: ['PUCHWEBTV', 'U330000']);
        $this->episode('Podcast only', tags: ['PUCHPODCAST', 'U310000']);
        $service = $this->service(tagFeedsChannel: 'PUCHWEBTV', tagFeedsGroups: ['cienciassalud' => ['U310000', 'U240000']]);

        $this->assertSame(['Biology', 'Health'], $this->titlesOf($service->tag('cienciassalud', 'https://tv.example.com')->xml));
        $this->assertSame(['Technology'], $this->titlesOf($service->tag('U330000', 'https://tv.example.com')->xml));
        $this->assertNull($service->tag('humanidades', 'https://tv.example.com'));
    }

    public function testTheChannelUsesTheConfiguredLanguageImageAndCover(): void
    {
        $this->episode('Episode');

        $xml = new \SimpleXMLElement($this->service(image: '/images/cover.png', language: 'es')->latest(EpisodeType::AUDIO, 'https://tv.example.com')->xml);

        $this->assertSame('es', (string) $xml->channel->language);
        $this->assertSame('https://tv.example.com/images/cover.png', (string) $xml->channel->image->url);
        $this->assertSame('https://tv.example.com/images/cover.png', (string) $xml->channel->children(FeedWriter::ITUNES_NAMESPACE)->image->attributes()['href']);
        $this->assertSame('false', (string) $xml->channel->children(FeedWriter::ITUNES_NAMESPACE)->explicit);
    }

    public function testTheTextsWithoutValidXmlCharactersAreCleaned(): void
    {
        $this->assertSame('ab', FeedWriter::clean("a\x0Bb"));
        $this->assertSame('', FeedWriter::clean("\xC3\x28"));
    }

    private function service(int $feedLimit = 100, int $seriesFeedLimit = 500, ?string $image = null, string $language = 'en', string $tagFeedsChannel = 'PUCHPODCAST', array $tagFeedsGroups = []): FeedService
    {
        $configuration = new ConfigurationService('Channel', 'Description', 'Copyright', 'Education', 'Summary', 'Subtitle', 'Author', false, true, $feedLimit, $seriesFeedLimit, 0, $image, $language, $tagFeedsChannel, $tagFeedsGroups);
        $container = self::getContainer();

        return new FeedService(
            new EpisodeRepository($this->dm, $configuration),
            new FeedWriter(),
            $configuration,
            $container->get(UrlGeneratorInterface::class),
            $container->get(PicService::class),
            new ArrayAdapter(),
            ['title' => 'TV', 'description' => 'TV', 'email' => 'tv@example.com', 'logo' => '/logo.png'],
        );
    }

    private function titlesOf(string $xml): array
    {
        return array_map('strval', (new \SimpleXMLElement($xml))->xpath('/rss/channel/item/title'));
    }
}
