<?php
/**
 * restores from audit log
 */

namespace Graviton\CommonBundle\Component\Cache\Command;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class ClearRedisCommand extends Command
{

    /**
     * @var CacheItemPoolInterface
     */
    private CacheItemPoolInterface $cacheItemPool;

    /**
     * contructor
     *
     * @param CacheItemPoolInterface $versionFilePath cache pool.
     */
    public function __construct(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
        parent::__construct(null);
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('graviton:common:cache:clear-redis')
            ->setDescription(
                'Clears all data in redis if configured.'
            );
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->cacheItemPool instanceof RedisAdapter) {
            $output->writeln("Common: Redis is not configured, not flushing.");
            return Command::SUCCESS;
        }

        $this->cacheItemPool->clear();

        $output->writeln("Common: Cleared all redis keys");

        return Command::SUCCESS;
    }
}
