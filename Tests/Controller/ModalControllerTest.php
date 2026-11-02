<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Tests\Controller;

use Pumukit\PodcastBundle\Tests\PodcastTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;

/**
 * @internal
 *
 * @coversNothing
 */
class ModalControllerTest extends PodcastTestCase
{
    public function testTheModalIsNotPublic(): void
    {
        $episode = $this->episode('Episode');

        $this->client->request('GET', '/admin/podcast/model/mm/'.$episode->getId());

        $this->assertNotSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testTheModalShowsTheStatusAndTheSeriesFeeds(): void
    {
        $episode = $this->episode('Episode');
        $this->login();

        $crawler = $this->client->request('GET', '/admin/podcast/model/mm/'.$episode->getId());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('podcast', $crawler->filter('.badge.text-bg-warning')->text());
        $base = 'http://localhost/podcast/series/'.$this->series->getId();
        $this->assertSame(
            [$base.'/audio.xml', $base.'/video.xml', $base.'/collection.xml'],
            $crawler->filter('input[data-controller="core--select"]')->each(static fn ($input): string => $input->attr('value')),
        );
        $this->assertCount(0, $crawler->filter('script, style, [style], [onclick]'));
    }

    private function login(): void
    {
        $user = new User();
        $user->setUsername('editor');
        $user->setEmail('editor@example.com');
        $user->addRole(PermissionProfile::SCOPE_GLOBAL);
        $user->addRole('ROLE_ACCESS_MULTIMEDIA_SERIES');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->client->loginUser($user, 'pumukit');
    }
}
