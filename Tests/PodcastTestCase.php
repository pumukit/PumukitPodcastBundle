<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\Tests;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\PodcastBundle\DependencyInjection\PumukitPodcastExtension;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MediaType\Metadata\VideoAudio;
use Pumukit\SchemaBundle\Document\MediaType\Storage;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\i18nText;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\StorageUrl;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class PodcastTestCase extends PumukitTestCase
{
    public const DOWNLOAD_SERVER = 'https://dls.example.org';
    protected KernelBrowser $client;
    protected Series $series;
    protected string $file;
    protected string $storageDir;
    private array $previousEnv = [];
    private int $numericalId = 0;

    public function setUp(): void
    {
        $this->storageDir = (string) realpath(sys_get_temp_dir()).'/podcast-'.bin2hex(random_bytes(6));
        mkdir($this->storageDir.'/downloads', 0777, true);
        foreach (['PUMUKIT_STORAGE_DIR' => $this->storageDir, 'PUMUKIT_DOWNLOAD_SERVER_URL' => self::DOWNLOAD_SERVER] as $name => $value) {
            $this->previousEnv[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null];
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.document_manager');
        $this->clearBBDD();
        self::getContainer()->get(PumukitPodcastExtension::CACHE_POOL)->clear();

        foreach (['PUCHPODCAST', 'PUCHWEBTV', 'U310000', 'U240000', 'U330000'] as $code) {
            $tag = new Tag();
            $tag->setCod($code);
            $this->dm->persist($tag);
        }
        $this->series = $this->series('Physics & Maths');
        $this->dm->flush();

        $this->file = $this->storageDir.'/downloads/pumukit-podcast-'.bin2hex(random_bytes(4)).'.mp3';
        file_put_contents($this->file, str_repeat('a', 2048));
    }

    public function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }
        @rmdir($this->storageDir.'/downloads');
        @rmdir($this->storageDir);
        foreach ($this->previousEnv as $name => [$env, $server]) {
            if (null === $env) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $env;
            }
            if (null === $server) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $server;
            }
        }
        parent::tearDown();
    }

    protected function series(string $title, bool $hide = false): Series
    {
        $series = new Series();
        $series->setNumericalID(++$this->numericalId);
        $series->setTitle($title, 'en');
        $series->setHide($hide);
        $this->dm->persist($series);

        return $series;
    }

    protected function episode(string $title, array $tags = ['PUCHPODCAST'], array $trackTags = ['podcast', 'audio'], string $date = '2026-01-01', ?Series $series = null, array $options = []): MultimediaObject
    {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setNumericalID(++$this->numericalId);
        $multimediaObject->setLocale('en');
        $multimediaObject->setTitle($title, 'en');
        $multimediaObject->setStatus($options['status'] ?? MultimediaObject::STATUS_PUBLISHED);
        $multimediaObject->setPublicDate(new \DateTime($date));
        $multimediaObject->setRecordDate(new \DateTime($date));
        $multimediaObject->setDuration(3725);
        $multimediaObject->setSeries($series ?? $this->series);
        $broadcast = new EmbeddedBroadcast();
        $broadcast->setType($options['broadcast'] ?? EmbeddedBroadcast::TYPE_PUBLIC);
        $multimediaObject->setEmbeddedBroadcast($broadcast);
        foreach ($tags as $code) {
            $multimediaObject->addTag($this->dm->getRepository(Tag::class)->findOneBy(['cod' => $code]));
        }
        if ([] !== $trackTags) {
            $multimediaObject->addTrack($this->track($trackTags, $options['hide'] ?? false, $options['path'] ?? null, $options['url'] ?? null));
        }
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    protected function track(array $tags, bool $hide = false, ?string $path = null, ?string $url = null): Track
    {
        $path ??= $this->file;

        return Track::create(
            basename($path),
            i18nText::create(['en' => '']),
            'en',
            Tags::create($tags),
            $hide,
            true,
            0,
            Storage::create(StorageUrl::create($url ?? '/storage/podcast/'.basename($path)), '' === $path ? null : Path::create($path)),
            VideoAudio::create('{"format":{"duration":"10.000000","size":"2048"}}')
        );
    }

    protected function xml(): \SimpleXMLElement
    {
        return new \SimpleXMLElement($this->client->getResponse()->getContent());
    }

    protected function titles(): array
    {
        return array_map('strval', $this->xml()->xpath('/rss/channel/item/title'));
    }
}
