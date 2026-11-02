<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Tests\Controller;

use Pumukit\PodcastBundle\Services\FeedWriter;
use Pumukit\PodcastBundle\Tests\PodcastTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 *
 * @coversNothing
 */
class FeedControllerTest extends PodcastTestCase
{
    public function testTheAudioFeedOnlyListsThePublishedPublicEpisodesNewestFirst(): void
    {
        $this->episode('Old', date: '2025-03-01');
        $this->episode('New', date: '2026-03-01');
        $this->episode('Blocked', options: ['status' => MultimediaObject::STATUS_BLOCKED]);
        $this->episode('Private', options: ['broadcast' => EmbeddedBroadcast::TYPE_LOGIN]);
        $this->episode('Video', trackTags: ['podcast']);
        $this->episode('Hidden track', options: ['hide' => true]);
        $this->episode('Other channel', tags: ['PUCHWEBTV']);

        $this->client->request('GET', '/podcast/audio.xml');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/xml; charset=UTF-8');
        $this->assertSame(['New', 'Old'], $this->titles());
    }

    public function testTheVideoFeedOnlyListsTheVideoEpisodes(): void
    {
        $this->episode('Audio');
        $this->episode('Video', trackTags: ['podcast']);

        $this->client->request('GET', '/podcast/video.xml');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Video'], $this->titles());
    }

    public function testAnEpisodeLinksToItsPublicEnclosureAndKeepsItsGuid(): void
    {
        $episode = $this->episode('Episode', tags: ['PUCHPODCAST', 'PUCHWEBTV']);
        $track = $episode->getTracks()->first();

        $this->client->request('GET', '/podcast/audio.xml');

        $xml = $this->xml();
        $item = $xml->channel->item[0];
        $this->assertSame('http://localhost/podcast/media/'.$track->id().'.mp3', (string) $item->enclosure['url']);
        $this->assertSame('audio/mpeg', (string) $item->enclosure['type']);
        $this->assertSame('2048', (string) $item->enclosure['length']);
        $this->assertSame('http://localhost/storage/podcast/'.basename($this->file), (string) $item->guid);
        $this->assertSame('false', (string) $item->guid['isPermaLink']);
        $this->assertSame('3725', (string) $item->children(FeedWriter::ITUNES_NAMESPACE)->duration);
        $this->assertStringContainsString('/'.$episode->getId(), (string) $item->link);
        $this->assertSame((new \DateTime('2026-01-01'))->format(\DATE_RSS), (string) $item->pubDate);
        $this->assertSame('http://localhost/podcast/audio.xml', (string) $xml->channel->children(FeedWriter::ATOM_NAMESPACE)->link->attributes()['href']);
        $this->assertStringNotContainsString('itunesu', $this->client->getResponse()->getContent());
    }

    public function testAnEpisodeWithoutItsFileIsLeftOut(): void
    {
        $missing = dirname($this->file).'/missing-'.basename($this->file);
        touch($missing);
        $this->episode('Missing file', date: '2026-06-01', options: ['path' => $missing]);
        unlink($missing);
        $this->episode('Available');

        $this->client->request('GET', '/podcast/audio.xml');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['Available'], $this->titles());
    }

    public function testTheSeriesFeedsEscapeTheTextsAndHideTheHiddenSeries(): void
    {
        $this->episode('Audio <1> & more');
        $this->episode('Video', trackTags: ['podcast']);

        $this->client->request('GET', '/podcast/series/'.$this->series->getId().'/collection.xml');
        $this->assertResponseIsSuccessful();
        $this->assertSame('Physics & Maths', (string) $this->xml()->channel->title);
        $this->assertEqualsCanonicalizing(['Audio <1> & more', 'Video'], $this->titles());

        $this->client->request('GET', '/podcast/series/'.$this->series->getId().'/audio.xml');
        $this->assertSame(['Audio <1> & more'], $this->titles());

        $hidden = $this->series('Hidden', true);
        $this->dm->flush();
        $this->client->request('GET', '/podcast/series/'.$hidden->getId().'/video.xml');
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/podcast/series/not-an-id/video.xml');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheTagFeedNeedsAnExistingTag(): void
    {
        $this->episode('Health', tags: ['PUCHPODCAST', 'U310000']);
        $this->episode('Other');

        $this->client->request('GET', '/podcast/U310000/audio.xml');
        $this->assertResponseIsSuccessful();
        $this->assertSame(['Health'], $this->titles());

        $this->client->request('GET', '/podcast/UNKNOWN/audio.xml');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheListLinksTheCollectionOfTheVisibleSeries(): void
    {
        $this->episode('Visible');
        $hidden = $this->series('Hidden', true);
        $this->episode('In a hidden series', series: $hidden);

        $this->client->request('GET', '/podcast/list.xml');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['http://localhost/podcast/series/'.$this->series->getId().'/collection.xml'], array_map('strval', $this->xml()->xpath('/list/podcast')));
    }

    public function testTheFeedIsServedFromTheCacheAndRevalidated(): void
    {
        $this->episode('First');

        $this->client->request('GET', '/podcast/audio.xml');
        $response = $this->client->getResponse();
        $etag = $response->getEtag();
        $this->assertNotEmpty($etag);
        $this->assertTrue($response->isCacheable());
        $this->assertGreaterThan(0, $response->getMaxAge());

        $this->episode('Second', date: '2026-06-01');
        $this->client->request('GET', '/podcast/audio.xml');
        $this->assertSame(['First'], $this->titles());
        $this->assertSame($etag, $this->client->getResponse()->getEtag());

        $this->client->request('GET', '/podcast/audio.xml', server: ['HTTP_IF_NONE_MATCH' => $etag]);
        $this->assertResponseStatusCodeSame(304);
        $this->assertSame('', $this->client->getResponse()->getContent());
    }
}
