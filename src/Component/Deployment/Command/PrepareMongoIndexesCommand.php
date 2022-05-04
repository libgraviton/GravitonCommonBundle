<?php
/**
 * prepares to create necessary indexes
 */
namespace Graviton\CommonBundle\Component\Deployment\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use MongoDB\Driver\Exception\CommandException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class PrepareMongoIndexesCommand extends Command
{
    /**
     * @var DocumentManager
     */
    private $manager;
    /**
     * @param DocumentManager $manager manager
     */
    public function __construct(
        DocumentManager $manager
    ) {
        $this->manager = $manager;
        parent::__construct();
    }
    /**
     * set up command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('graviton:core:prepare-mongodb-index-update')
            ->setDescription(
                'Prepares for the mongodb update indexes command'
            );
    }
    /**
     * run command
     *
     * @param InputInterface  $input  input interface
     * @param OutputInterface $output output interface
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        foreach ($this->manager->getMetadataFactory()->getAllMetadata() as $class) {
            assert($class instanceof ClassMetadata);
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument || $class->isView()) {
                continue;
            }

            $this->updateIndexesForClass($class->name);
        }

        return 0;
    }

    private function updateIndexesForClass(string $className) {
        try {
            $this->manager->getSchemaManager()
                          ->ensureDocumentIndexes($className);
        } catch (CommandException $e) {
            // assume some name collision -> delete all indexes..
            $this->manager->getSchemaManager()->deleteDocumentIndexes($className);
        }
    }
}
