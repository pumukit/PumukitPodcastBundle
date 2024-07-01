<?php

declare(strict_types=1);

namespace Pumukit\PodcastBundle\DependencyInjection;

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
