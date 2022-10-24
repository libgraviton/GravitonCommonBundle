<?php
/**
 * Stopwatch
 */
namespace Graviton\CommonBundle\Component\Tracing;

use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * Class Stopwatch
 *
 * @package GatewaySecurityBundle\Security
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class Stopwatch
{

    private \Symfony\Component\Stopwatch\Stopwatch $stopwatch;

    public function __construct() {
        $this->stopwatch = new \Symfony\Component\Stopwatch\Stopwatch(true);
    }

    public function start(string $name, ?string $category = null) {
        $this->stopwatch->start($name, $category);
    }

    public function stop(string $name) {
        $this->stopwatch->stop($name);
    }

    public function getEvent(string $name) : ?StopwatchEvent {
        $event = null;
        try {
            $event = $this->stopwatch->getEvent($name);
        } catch (\Throwable $t) {
        }

        return $event;
    }

    public function __toString(): string {
        $parts = [];
        foreach ($this->stopwatch->getSections() as $section) {
            foreach ($section->getEvents() as $event) {
                $parts[] = (string) $event;
            }
        }
        return implode(' / ', $parts);
    }
}
