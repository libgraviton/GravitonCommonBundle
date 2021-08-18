<?php

namespace Graviton\CommonBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     * graviton_common:
     * cache:
    * instance_id: "%graviton.cache.instance_id%"
    * redis_host: "%graviton.cache.redis.host%"
    * redis_port: "graviton.cache.redis.port"
    * adapter_override: null
     */

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('graviton_common');

        $treeBuilder->getRootNode()
                        ->children()
                            ->arrayNode('cache')
                                ->children()
                                    ->scalarNode('instance_id')->defaultValue('grv')->end()
                                    ->scalarNode('redis_host')->defaultNull()->end()
                                    ->integerNode('redis_port')->defaultNull()->end()
                                    ->scalarNode('adapter_override')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
        ;

        return $treeBuilder;
    }
}
