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

        // cache
        $container->setParameter('graviton.common.cache.instance_id', $config['cache']['instance_id']);
        $container->setParameter('graviton.common.cache.redis_host', $config['cache']['redis_host']);
        $container->setParameter('graviton.common.cache.redis_port', $config['cache']['redis_port']);
        $container->setParameter('graviton.common.cache.adapter_override', $config['cache']['adapter_override']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');
    }
}
