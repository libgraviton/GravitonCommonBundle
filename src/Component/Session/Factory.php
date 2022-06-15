<?php
/**
 * session factory
 */

namespace Graviton\CommonBundle\Component\Session;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Factory
{

    private $redisHost;
    private $redisPort;
    private $redisDb;
    private $fallbackSession;

    /**
     * @param string $redisHost redis host
     * @param ?int   $redisPort redis port
     * @param ?int   $redisDb   redis db
     */
    public function __construct($redisHost, ?int $redisPort, ?int $redisDb, $fallbackSession)
    {
        $this->redisHost = $redisHost;
        $this->redisPort = $redisPort;
        $this->redisDb = $redisDb;
        $this->fallbackSession = $fallbackSession;
    }

    /**
     * gets instance
     *
     * @return CacheItemPoolInterface cache
     */
    public function getInstance() : AbstractSessionHandler
    {
        if ($this->redisHost != null) {
            $redis = new \Redis();
            $redis->connect($this->redisHost, $this->redisPort);
            $redis->select($this->redisDb);
            return new RedisSessionHandler($redis);
        }

        return $this->fallbackSession;
    }
}
