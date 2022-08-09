<?php
/**
 * GlobalTracer
 */

namespace Graviton\CommonBundle\Component\Tracing;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class GlobalTracer
{

    public static function init() : void {
        \OpenTracing\GlobalTracer::set(new MyTracerImplementation());
    }

}
