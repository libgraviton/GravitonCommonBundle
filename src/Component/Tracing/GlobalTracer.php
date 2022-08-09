<?php
/**
 * GlobalTracer
 */

namespace Graviton\CommonBundle\Component\Tracing;

use Jaeger\Config;
use OpenTracing\Tracer;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class GlobalTracer
{
    public static function init() : void {
        $config = new Config(
            [
                'sampler' => [
                    'type' => \Jaeger\SAMPLER_TYPE_CONST,
                    'param' => true,
                ],
                'logging' => true,
            ],
            'your-app-name'
        );
        $config->initializeTracer();
    }

    public static function get() : Tracer {
        return \OpenTracing\GlobalTracer::get();
    }
}
