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
                            ->arrayNode('http')->isRequired()
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('cors')->isRequired()
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('origins_credentials_allowed')->defaultNull()->end()
                                            ->scalarNode('origins_allowed')->defaultNull()->end()
                                            ->arrayNode('headers_allowed')->isRequired()->prototype('scalar')->end()->end()
                                            ->arrayNode('headers_exposed')->isRequired()->prototype('scalar')->end()->end()
                                            ->arrayNode('methods_allowed')->isRequired()->prototype('scalar')->end()->end()
                                        ->end()
                                    ->end()
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

                            ->arrayNode('logging')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('logging_masker_service_id')->defaultValue('Graviton\CommonBundle\Component\Logging\DummyLoggingMasker')->end()
                                ->end()
                            ->end()

                            ->arrayNode('audit')
                                ->children()
                                    ->addDefaultsIfNotSet()
                                    ->booleanNode('enabled')->defaultFalse()->end()
                                    ->booleanNode('active_user_tracking_enabled')->defaultFalse()->end()
                                    ->booleanNode('fallback_mongodb')->defaultFalse()->end() // should we fall back to mongodb when nothing is configured?
                                    ->scalarNode('response_header_name')->isRequired()->end()
                                    ->scalarNode('skip_on_header_presence')->defaultNull()->end() // skip logging when this request header is present
                                    ->scalarNode('app_name')->isRequired()->end()
                                    ->scalarNode('logger_url')->defaultNull()->end()
                                    ->scalarNode('log_database')->defaultValue('gateway')->end()
                                    ->scalarNode('log_collection')->defaultValue('SecurityUserAudit')->end()
                                    ->booleanNode('record_payload')->defaultFalse()->end()
                                    ->arrayNode('record_payload_exceptions')->prototype('scalar')->end()->end()
                                    ->arrayNode('ignore_methods')->prototype('scalar')->end()->end()
                                ->end()
                            ->end()

                            ->arrayNode('proxy')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('proxy_parameter_name')->defaultValue('graviton.proxy')->end()
                                    ->scalarNode('no_proxy_parameter_name')->defaultValue('graviton.noproxy')->end()
                                ->end()
                            ->end()
                        ->end();

        return $treeBuilder;
    }
}
