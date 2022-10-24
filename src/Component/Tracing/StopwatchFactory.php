<?php
/**
 * StopwatchFactory
 */
namespace Graviton\CommonBundle\Component\Tracing;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class StopwatchFactory
 *
 * @package GatewaySecurityBundle\Security
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class StopwatchFactory
{

    private \Symfony\Component\Stopwatch\Stopwatch $stopwatch;

    public function __construct() {
        $this->stopwatch = new \Symfony\Component\Stopwatch\Stopwatch();
    }

    public function getInstance() : Stopwatch {
        return $this->stopwatch;
    }
}
