<?php
/**
 * lock factory
 */

namespace Graviton\CommonBundle\Component\Lock;

use Graviton\CommonBundle\Component\Redis\OptionalRedis;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\RedisStore;
use Symfony\Component\Lock\Store\SemaphoreStore;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
readonly class Factory
{

  /**
   * @param OptionalRedis $optionalRedis redis
   */
  public function __construct(private OptionalRedis $optionalRedis)
  {
  }

  public function getInstance(): LockFactory
  {
    if ($this->optionalRedis->isConfigured()) {
      $store = new RedisStore($this->optionalRedis->getInstance());
    } else {
      $store = new SemaphoreStore();
    }

    return new LockFactory($store);
  }
}
