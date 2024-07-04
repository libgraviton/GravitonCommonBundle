<?php
/**
 * OptionalRedis
 */

namespace Graviton\CommonBundle\Component\Redis;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class OptionalRedis
{

  private ?string $redisHost;
  private ?int $redisPort;
  private ?int $redisDb;

  /**
   * @param ?string $redisHost redis host
   * @param ?int    $redisPort redis port
   * @param ?int    $redisDb   redis db
   */
  public function __construct(?string $redisHost, ?int $redisPort, ?int $redisDb)
  {
    $this->redisHost = $redisHost;
    $this->redisPort = $redisPort;
    $this->redisDb = $redisDb;
  }

  public function isConfigured() : bool {
    return is_string($this->redisHost);
  }

  public function getInstance(): ?\Redis {
    if (!$this->isConfigured()) {
      return null;
    }

    $redis = new \Redis();
    $redis->connect($this->redisHost, $this->redisPort);
    $redis->select($this->redisDb);

    return $redis;
  }
}
