<?php
/** removes the cors listener if not defined */

namespace Graviton\CommonBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class CorsListenerCompilerPass implements CompilerPassInterface
{

    /**
     *
     * @param ContainerBuilder $container Container
     *
     * @return void
     */
    public function process(ContainerBuilder $container)
    {
        $hasParam = $container->hasParameter('graviton.common.http.cors.headers_allowed');
        if (!$hasParam) {
            // remove listener!
            $container->removeDefinition('Graviton\CommonBundle\Component\Http\Listener\CorsResponseListener');
        }
    }
}
