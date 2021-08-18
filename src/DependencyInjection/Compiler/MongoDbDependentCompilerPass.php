<?php
/** removes mongodb dependent services if necessary */

namespace Graviton\CommonBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class MongoDbDependentCompilerPass implements CompilerPassInterface
{

    /**
     * proxy settings
     *
     * @var array
     */
    private $proxySettings = [];

    /**
     * add guzzle options
     *
     * @param ContainerBuilder $container Container
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $mongoDmServiceId = $container->getParameter('graviton.common.mongo_document_manager_service_id');
        if (is_null($mongoDmServiceId)) {
            foreach ($container->findTaggedServiceIds('mongodb_dependent') as $serviceId => $args) {
                $container->removeDefinition($serviceId);
            }
        }
    }
}
