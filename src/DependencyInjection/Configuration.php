<?php

namespace Graviton\CommonBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder() : TreeBuilder
    {
        $treeBuilder = new TreeBuilder('graviton_common');

        $treeBuilder->getRootNode()
                        ->children()
                            ->scalarNode('mongo_document_manager_service_id')->defaultNull()->end()
                            ->arrayNode('cache')
                                ->children()
                                    ->scalarNode('instance_id')->defaultValue('grv')->end()
                                    ->scalarNode('redis_host')->defaultNull()->end()
                                    ->integerNode('redis_port')->defaultNull()->end()
                                    ->integerNode('redis_db')->defaultValue(1)->end()
                                    ->scalarNode('adapter_override')->defaultNull()->end()
                                ->end()
                            ->end()
                            ->arrayNode('http_client')
                                ->children()
                                    ->variableNode('options')->defaultValue([])->end()
                                    ->booleanNode('verify_peer')->defaultTrue()->end()
                                    ->booleanNode('debug_requests')->defaultFalse()->end()
                                    ->integerNode('debug_max_length')->defaultValue(5000)->end()
                                ->end()
                            ->end()
                            ->arrayNode('deployment')
                                ->children()
                                    ->scalarNode('check_package_name')->defaultValue('')->end()
                                ->end()
                            ->end()
                            ->arrayNode('proxy')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('proxy_parameter_name')->defaultValue('graviton.proxy')->end()
                                    ->scalarNode('no_proxy_parameter_name')->defaultValue('graviton.noproxy')->end()
                                ->end()
                            ->end()
                        ->end()
        ;

        return $treeBuilder;
    }
}
