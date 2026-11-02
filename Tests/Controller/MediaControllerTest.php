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
    public function testThePublishedEpisodeIsDeliveredThroughTheProxy(): void
    {
        $episode = $this->episode('Episode');

        $this->client->request('GET', $this->url($episode), server: [
            'HTTP_X_SENDFILE_TYPE' => 'X-Accel-Redirect',
            'HTTP_X_ACCEL_MAPPING' => dirname($this->file).'/=/internal-storage/',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('X-Accel-Redirect', rawurlencode('/internal-storage/'.basename($this->file)));
        $this->assertStringContainsString('inline', $this->client->getResponse()->headers->get('Content-Disposition'));
    }

    public function testPhpSendsTheEpisodeWithoutTheProxy(): void
    {
        $episode = $this->episode('Episode');

        $this->client->request('GET', $this->url($episode));

        $this->assertResponseIsSuccessful();
        $this->assertSame(2048, strlen($this->client->getInternalResponse()->getContent()));

        $this->client->request('HEAD', $this->url($episode));
        $this->assertResponseIsSuccessful();
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
