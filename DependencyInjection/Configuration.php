<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\DependencyInjection;

use Pumukit\PodcastBundle\PumukitPodcastBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pumukit_podcast');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->booleanNode('enable')
            ->info('If false, the feeds, the episodes and the backoffice modal return 404')
            ->defaultTrue()
            ->end()
            ->integerNode('feed_limit')
            ->info('Maximum number of episodes, newest first, of the global feeds and the tag feeds')
            ->defaultValue(100)
            ->min(1)
            ->end()
            ->integerNode('series_feed_limit')
            ->info('Maximum number of episodes, newest first, of the series feeds')
            ->defaultValue(500)
            ->min(1)
            ->end()
            ->integerNode('cache_ttl')
            ->info('Seconds a generated feed is served from the cache. 0 generates it on every request')
            ->defaultValue(1800)
            ->min(0)
            ->end()
            ->scalarNode('image')
            ->info('Absolute or relative URL of the channel cover. If not defined, it takes the pumukit.info logo')
            ->defaultNull()
            ->end()
            ->scalarNode('language')
            ->info('Language of the feeds and of the episode texts. If not defined, it takes the default locale')
            ->defaultNull()
            ->end()
            ->arrayNode('tag_feeds')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('channel')
            ->info('Publication channel the episodes of /podcast/{tag}/audio.xml must have')
            ->defaultValue(PumukitPodcastBundle::PODCAST_TAG)
            ->cannotBeEmpty()
            ->end()
            ->arrayNode('groups')
            ->info('Feeds /podcast/{name}/audio.xml with the episodes of any of these tag codes')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->requiresAtLeastOneElement()
            ->scalarPrototype()->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->scalarNode('channel_title')
            ->info('Title of the channel')
            ->defaultValue('PuMuKIT Channel')
            ->end()
            ->scalarNode('channel_description')
            ->info('Description of the channel')
            ->defaultValue('PuMuKIT description of the channel')
            ->end()
            ->scalarNode('channel_copyright')
            ->info('Copyright of the channel')
            ->defaultValue('PuMuKIT Team 2015')
            ->end()
            ->scalarNode('itunes_category')
            ->info('Itunes category of the channel. Default value: Education. This value must be in English: https://validator.w3.org/feed/docs/error/InvalidItunesCategory.html')
            ->defaultValue('Education')
            ->end()
            ->scalarNode('itunes_summary')
            ->info('Itunes summary of the channel')
            ->defaultValue('Education channel')
            ->end()
            ->scalarNode('itunes_subtitle')
            ->info('Itunes subtitle of the channel')
            ->defaultValue('Itunes subtitle')
            ->end()
            ->scalarNode('itunes_author')
            ->info('Itunes author of the channel. Default value: PuMuKIT-TV')
            ->defaultValue('PuMuKIT-TV')
            ->end()
            ->booleanNode('itunes_explicit')
            ->info('Itunes is explicit. Default value: false')
            ->defaultValue(false)
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
