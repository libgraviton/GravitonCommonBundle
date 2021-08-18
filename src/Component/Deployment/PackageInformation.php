<?php
/**
 * Utility for version information
 */

namespace Graviton\CommonBundle\Component\Deployment;

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
class PackageInformation
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

}
