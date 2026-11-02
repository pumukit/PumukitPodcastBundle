<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

class PumukitPodcastExtension extends Extension implements PrependExtensionInterface
{
    public const CACHE_POOL = 'pumukit_podcast.cache';

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'cache' => [
                'pools' => [
                    self::CACHE_POOL => ['adapter' => 'cache.app'],
                ],
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('pumukit_podcast.enable', $config['enable']);
        $container->setParameter('pumukit_podcast.feed_limit', $config['feed_limit']);
        $container->setParameter('pumukit_podcast.series_feed_limit', $config['series_feed_limit']);
        $container->setParameter('pumukit_podcast.cache_ttl', $config['cache_ttl']);
        $container->setParameter('pumukit_podcast.image', $config['image']);
        $container->setParameter('pumukit_podcast.language', $config['language'] ?? '%kernel.default_locale%');
        $container->setParameter('pumukit_podcast.tag_feeds.channel', $config['tag_feeds']['channel']);
        $container->setParameter('pumukit_podcast.tag_feeds.groups', $config['tag_feeds']['groups']);
        $container->setParameter('pumukit_podcast.channel_title', $config['channel_title']);
        $container->setParameter('pumukit_podcast.channel_description', $config['channel_description']);
        $container->setParameter('pumukit_podcast.channel_copyright', $config['channel_copyright']);
        $container->setParameter('pumukit_podcast.itunes_category', $config['itunes_category']);
        $container->setParameter('pumukit_podcast.itunes_summary', $config['itunes_summary']);
        $container->setParameter('pumukit_podcast.itunes_subtitle', $config['itunes_subtitle']);
        $container->setParameter('pumukit_podcast.itunes_author', $config['itunes_author']);
        $container->setParameter('pumukit_podcast.itunes_explicit', $config['itunes_explicit']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('pumukit_podcast.yaml');
    }
}
