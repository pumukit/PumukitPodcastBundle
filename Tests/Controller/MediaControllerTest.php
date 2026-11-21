<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Tests\Controller;

use Pumukit\PodcastBundle\Tests\PodcastTestCase;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @internal
 *
 * @coversNothing
 */
class MediaControllerTest extends PodcastTestCase
{
    public function testThePublishedEpisodeIsRedirectedToTheDownloadServer(): void
    {
        $episode = $this->episode('Episode');

        $this->client->request('GET', $this->url($episode));

        $this->assertResponseRedirects();
        $location = (string) $this->client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith(self::DOWNLOAD_SERVER.'/files/downloads/'.basename($this->file).'?token=', $location);
        $this->assertStringNotContainsString('forcedl', $location);

        $this->client->request('HEAD', $this->url($episode));
        $this->assertResponseRedirects();
    }

    public function testAnExternalEpisodeIsRedirected(): void
    {
        $episode = $this->episode('External', options: ['path' => '', 'url' => 'https://cdn.example.com/episode.mp3']);

        $this->client->request('GET', $this->url($episode));

        $this->assertResponseRedirects('https://cdn.example.com/episode.mp3');
    }

    public function testOnlyThePublicPodcastTracksAreDelivered(): void
    {
        $missing = dirname($this->file).'/missing-'.basename($this->file);
        touch($missing);
        $missingFile = $this->episode('Missing file', options: ['path' => $missing]);
        unlink($missing);

        $episodes = [
            $missingFile,
            $this->episode('Blocked', options: ['status' => MultimediaObject::STATUS_BLOCKED]),
            $this->episode('Private', options: ['broadcast' => EmbeddedBroadcast::TYPE_LOGIN]),
            $this->episode('Hidden track', options: ['hide' => true]),
            $this->episode('Not podcast', trackTags: ['display', 'audio']),
            $this->episode('Other channel', tags: ['PUCHWEBTV']),
        ];

        foreach ($episodes as $episode) {
            $this->client->request('GET', $this->url($episode));
            $this->assertResponseStatusCodeSame(404, $episode->getTitle());
        }

        $this->client->request('GET', '/podcast/media/0123456789abcdef01234567.mp3');
        $this->assertResponseStatusCodeSame(404);
    }

    private function url(MultimediaObject $episode): string
    {
        return '/podcast/media/'.$episode->getTracks()->first()->id().'.mp3';
    }
}
