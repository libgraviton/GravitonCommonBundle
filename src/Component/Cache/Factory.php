<?php
/**
 * cache factory
 */

namespace Graviton\CommonBundle\Component\Cache;

use Graviton\CommonBundle\Component\Redis\OptionalRedis;
use Monolog\Logger;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Factory
{

    public const ADAPTER_ARRAY = 'array';

    private CacheItemPoolInterface $appCache;
    private ?string $instanceId;
    private ?string $adapterOverride;
    private Logger $logger;
    private OptionalRedis $optionalRedis;

    /**
     * @param CacheItemPoolInterface $appCache        cache by symfony
     * @param string                 $instanceId      instance id
     * @param string                 $adapterOverride override
     * @param OptionalRedis          $optionalRedis   redis
     */
    public function __construct(CacheItemPoolInterface $appCache, $instanceId, $adapterOverride, Logger $logger, OptionalRedis $optionalRedis)
    {
        $this->appCache = $appCache;
        $this->instanceId = $instanceId;
        $this->adapterOverride = $adapterOverride;
        $this->logger = $logger;
        $this->optionalRedis = $optionalRedis;
    }

    /**
     * gets instance
     *
     * @return CacheItemPoolInterface cache
     */
    public function getInstance() : CacheItemPoolInterface
    {
        if ($this->adapterOverride == self::ADAPTER_ARRAY) {
            // forced array adapter
            $this->logger->info("Using cache adapter with 'array' adapter.");
            return new ArrayAdapter();
        }

        if ($this->optionalRedis->isAvailable()) {
            $this->logger->info("Using cache adapter with 'redis' adapter.");
            return new RedisAdapter($this->optionalRedis->getInstance(), $this->instanceId);
        }

        $this->logger->info("Falling back to app cache.", ['class' => get_class($this->appCache)]);

        return $this->appCache;
    }
}
