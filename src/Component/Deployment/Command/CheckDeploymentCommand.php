<?php
/**
 * Command that checks if we need to run database stuff on the current deploy
 */

namespace Graviton\CommonBundle\Component\Deployment\Command;

use Composer\InstalledVersions;
use Doctrine\ODM\MongoDB\DocumentManager;
use Graviton\CommonBundle\Document\Deployment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author  List of contributors <https://github.com/libgraviton/DeploymentServiceBundle/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class CheckDeploymentCommand extends Command
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $selfPackageName;

    /**
     * Constructor.
     *
     * @param DocumentManager $dm              document manager
     * @param string          $selfPackageName self package name
     */
    public function __construct(DocumentManager $dm, string $selfPackageName)
    {
        $this->dm = $dm;
        $this->selfPackageName = $selfPackageName;
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

        $this->setName('graviton:check-deployment')
            ->addOption('hash', null, InputOption::VALUE_REQUIRED, 'optional hash to check', null)
            ->setDescription(
                'Checks if for the current commit hash it is needed to run external on-startup deployment things'
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
        // sleep random amount of time between 0.5 and 3s
        $randsleep = mt_rand(0.5, 2500);
        usleep($randsleep * 1000);

        $repo = $this->dm->getRepository(Deployment::class);

        $currentCommitHash = InstalledVersions::getReference($this->selfPackageName);

        // what we search/persist
        $packageName = $this->selfPackageName;
        $gitHash = $currentCommitHash;

        $customHash = $input->getOption('hash');
        if (!is_null($customHash)) {
            $packageName = 'custom-hash';
            $gitHash = $customHash;
        }

        // something there for current hash?
        $existing = $repo->findOneBy([
            'packageName' => $packageName,
            'commitHash' => $gitHash
        ]);

        if (!is_null($existing)) {
            // say no -> nothing needs to be done!
            echo 'NO';
            return 0;
        }

        $deployment = new Deployment();
        $deployment->setPackageName($packageName);
        $deployment->setCommitHash($gitHash);
        $deployment->setCreatedAt(new \DateTime());

        $this->dm->persist($deployment);
        $this->dm->flush();

        echo 'YES';

        return 0;
    }
}
