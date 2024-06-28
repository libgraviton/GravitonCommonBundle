<?php
/**
 * generate indexes
 */

namespace Graviton\CommonBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Graviton\CommonBundle\Document\Deployment;
use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Model\IndexInfo;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Here, we generate all "dynamic" Graviton bundles..
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class DocumentIndexesCommand extends Command
{

    private Logger $logger;
    private DocumentManager $manager;
    private array $usedClasses = [];

    /**
     * @var array we only want to process documents that are referenced! not embeds!
     */
    private array $relevantAssociations = [ClassMetadata::REFERENCE_ONE, ClassMetadata::REFERENCE_MANY];

    public function __construct(
        Logger $logger,
        DocumentManager $manager
    ) {
        parent::__construct('graviton:ensure-indexes');
        $this->logger = $logger;
        $this->manager = $manager;
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     *
     * @return int exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->manager->getMetadataFactory()->getAllMetadata() as $class) {
            $this->workOnClass($class);
        }

        // delete unused collections!
        $this->deleteUnusedCollections(
            $this->manager->getClient(),
            $this->manager->getDocumentDatabase(Deployment::class)
        );

        foreach ($this->usedClasses as $name => $collectionName) {
            $this->updateDocumentIndexes($name);
        }

        return Command::SUCCESS;
    }

    private function workOnClass(ClassMetadata $class)
    {
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument || $class->isQueryResultDocument || $class->isView()) {
            return;
        }

        $this->usedClasses[$class->getName()] = $class->getCollection();

        // iterate fields
        foreach ($class->getFieldNames() as $fieldName) {
            $mapping = $class->getFieldMapping($fieldName);
            if ($class->hasAssociation($fieldName) && in_array($mapping['association'], $this->relevantAssociations)) {
                $targetClass = $class->getAssociationTargetClass($fieldName);
                $this->workOnClass($this->manager->getMetadataFactory()->getMetadataFor($targetClass));
            }
        }
    }

    private function deleteUnusedCollections(Client $client, Database $db): void
    {
        $mongoDb = $client->selectDatabase($db->getDatabaseName());
        $wantToKeep = array_values($this->usedClasses);

        foreach ($db->listCollectionNames() as $collectionName) {
            if (!in_array($collectionName, $wantToKeep) && !str_starts_with($collectionName, 'system.')) {
                try {
                    // recordcount?
                    $collection = $mongoDb->selectCollection($collectionName);
                    if ($collection->countDocuments() < 1) {
                        $mongoDb->dropCollection($collectionName);
                    }
                } catch (\Throwable $t) {
                    $this->logger->warning("Unable to count and delete collection '{$collectionName}'", ['err' => $t]);
                }
            }
        }
    }

    public function updateDocumentIndexes(string $documentName, bool $background = false): void
    {
        $documentIndexes = $this->manager->getSchemaManager()->getDocumentIndexes($documentName);
        $collection      = $this->manager->getDocumentCollection($documentName);
        $mongoIndexes    = iterator_to_array($collection->listIndexes());

        // which ones to delete?
        $mongoIndexes = array_filter($mongoIndexes, function (IndexInfo $mongoIndex) use ($documentIndexes) {
            if ($mongoIndex['name'] === '_id_') {
                return false;
            }

            foreach ($documentIndexes as $documentIndex) {
                if ($this->manager->getSchemaManager()->isMongoIndexEquivalentToDocumentIndex($mongoIndex, $documentIndex)) {
                    return false;
                }
            }

            return true;
        });

        // Delete indexes that do not exist in class metadata
        foreach ($mongoIndexes as $mongoIndex) {
            if (! isset($mongoIndex['name'])) {
                continue;
            }

            $collection->dropIndex($mongoIndex['name']);
        }

        // create all necessary ones!
        foreach ($documentIndexes as $index) {
            try {
                $collection->createIndex($index['keys'], $index['options'] + ['background' => $background]);
            } catch (\Throwable $t) {
                $this->logger->error("Error creating index: ".$t->getMessage().". Continuing..", ['err' => $t]);
            }
        }
    }
}
