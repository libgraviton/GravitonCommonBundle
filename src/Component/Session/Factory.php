<?php
/**
 * session factory
 */

namespace Graviton\CommonBundle\Component\Session;

use Graviton\CommonBundle\Component\Redis\OptionalRedis;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Factory
{

    private OptionalRedis $optionalRedis;
    private $fallbackSession;

    /**
     * @param OptionalRedis $optionalRedis redis
     */
    public function __construct(OptionalRedis $optionalRedis, $fallbackSession)
    {
        $this->optionalRedis = $optionalRedis;
        $this->fallbackSession = $fallbackSession;
    }

    /**
     * gets instance
     *
     * @return AbstractSessionHandler AbstractSessionHandler
     */
    public function getInstance() : AbstractSessionHandler
    {
        if ($this->optionalRedis->isAvailable()) {
            return new RedisSessionHandler($this->optionalRedis->getInstance());
        }

        return $this->fallbackSession;
    }
}
