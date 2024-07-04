<?php
/**
 * ExclusiveLockingCache
 */

namespace Graviton\CommonBundle\Component\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Lock\Key;
use Symfony\Component\Lock\LockFactory;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
readonly class ExclusiveLockingCache
{

    public function __construct(private CacheItemPoolInterface $cacheProvider, private LockFactory $lockFactory)
    {
    }

  /**
   * gets a CacheItem from a provider. if it doesn't exist, it will use the $computeCallback to compute the value.
   * it uses a global lock on the cache provider, so even multiple replicas will not do the same work if using
   * the same lock.
   *
   * @param string $cacheKey
   * @param callable $computeCallback
   * @param float $ttl
   * @param bool $autoRelease
   * @return CacheItem
   *
   * @throws \Psr\Cache\InvalidArgumentException
   */
  public function getCacheItemGlobalLock(string $cacheKey, callable $computeCallback, float $ttl = 300, bool $autoRelease = true) : CacheItem
  {
    $lockKey = new Key($cacheKey . '-lock');

    $item = $this->cacheProvider->getItem($cacheKey);
    if (!$item->isHit()) {
      // recompute!
      $lock = $this->lockFactory->createLockFromKey($lockKey, $ttl, $autoRelease);
      try {
        if (!$lock->acquire()) {
          while (!$lock->acquire()) {
          }
          $item = $this->cacheProvider->getItem($cacheKey);
        } else {
          $item = $computeCallback($item);
          $this->cacheProvider->save($item);
        }
      } finally {
        $lock->release();
      }
    }

    return $item;
  }
}
