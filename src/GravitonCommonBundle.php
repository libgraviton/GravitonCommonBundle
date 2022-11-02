<?php
/**
 * bundle class
 */

namespace Graviton\CommonBundle;

use Graviton\CommonBundle\DependencyInjection\Compiler\CorsListenerCompilerPass;
use Graviton\CommonBundle\DependencyInjection\Compiler\MongoDbDependentCompilerPass;
use Graviton\CommonBundle\DependencyInjection\Compiler\HttpClient\HttpClientOptionsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author  List of contributors <https://github.com/libgraviton/DeploymentServiceBundle/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class GravitonCommonBundle extends Bundle
{
    /**
     * load version compiler pass
     *
     * @param ContainerBuilder $container container builder
     *
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MongoDbDependentCompilerPass());
        $container->addCompilerPass(new HttpClientOptionsCompilerPass());
        $container->addCompilerPass(new CorsListenerCompilerPass());
    }
}
