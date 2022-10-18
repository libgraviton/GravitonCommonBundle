<?php

namespace Graviton\CommonBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class GravitonCommonExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        // top keys
        $container->setParameter('graviton.common.mongo_document_manager_service_id', $config['mongo_document_manager_service_id']);

        // cache
        $container->setParameter('graviton.common.cache.instance_id', $config['cache']['instance_id']);
        $container->setParameter('graviton.common.cache.redis_host', $config['cache']['redis_host']);
        $container->setParameter('graviton.common.cache.redis_port', $config['cache']['redis_port']);
        $container->setParameter('graviton.common.cache.redis_db', $config['cache']['redis_db']);
        $container->setParameter('graviton.common.cache.adapter_override', $config['cache']['adapter_override']);

        // http
        $container->setParameter('graviton.common.http.cors.allow_credentials', $config['http']['cors']['allow_credentials']);
        $container->setParameter('graviton.common.http.cors.origins_allowed', $config['http']['cors']['origins_allowed']);
        $container->setParameter('graviton.common.http.cors.headers_allowed', $config['http']['cors']['headers_allowed']);
        $container->setParameter('graviton.common.http.cors.headers_exposed', $config['http']['cors']['headers_exposed']);
        $container->setParameter('graviton.common.http.cors.methods_allowed', $config['http']['cors']['methods_allowed']);

        // http client
        $container->setParameter('graviton.common.http_client.options', $config['http_client']['options']);
        $container->setParameter('graviton.common.http_client.debug_requests', $config['http_client']['debug_requests']);
        $container->setParameter('graviton.common.http_client.debug_max_length', $config['http_client']['debug_max_length']);
        $container->setParameter('graviton.common.http_client.verify_peer', $config['http_client']['verify_peer']);

        // deployment
        $container->setParameter('graviton.common.deployment.check_package_name', $config['deployment']['check_package_name']);

        // proxy
        $container->setParameter('graviton.common.proxy.proxy_parameter_name', $config['proxy']['proxy_parameter_name']);
        $container->setParameter('graviton.common.proxy.no_proxy_parameter_name', $config['proxy']['no_proxy_parameter_name']);

        // audit
        $container->setParameter('graviton.common.audit.enabled', $config['audit']['enabled']);
        $container->setParameter('graviton.common.audit.active_user_tracking_enabled', $config['audit']['active_user_tracking_enabled']);
        $container->setParameter('graviton.common.audit.app_name', $config['audit']['app_name']);
        $container->setParameter('graviton.common.audit.response_header_name', $config['audit']['response_header_name']);
        $container->setParameter('graviton.common.audit.logger_url', $config['audit']['logger_url']);
        $container->setParameter('graviton.common.audit.log_database', $config['audit']['log_database']);
        $container->setParameter('graviton.common.audit.log_collection', $config['audit']['log_collection']);
        $container->setParameter('graviton.common.audit.record_payload', $config['audit']['record_payload']);
        $container->setParameter('graviton.common.audit.record_payload_exceptions', $config['audit']['record_payload_exceptions']);
        $container->setParameter('graviton.common.audit.ignore_methods', $config['audit']['ignore_methods']);

        // logging
        $container->setParameter('graviton.common.logging.logging_masker_service_id', $config['logging']['logging_masker_service_id']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');
    }
}
