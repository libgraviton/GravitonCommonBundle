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
class VersionInformation
{

    const SHORT_COMMIT_LENGTH = 7;

    /**
     * @var InstalledVersions
     */
    private $upstream;

    /**
     * Constructor.
     *
     * @param DocumentManager $dm              document manager
     * @param string          $selfPackageName self package name
     */
    public function __construct(InstalledVersions $upstream)
    {
        $this->upstream = $upstream;
    }

    public function getPrettyVersion($packageName) {
        $ver = $this->upstream::getPrettyVersion($packageName);

        if (str_starts_with($ver, 'dev-')) {
            $ver .= '@'.$this->getShortReference($packageName);
        }

        return $ver;
    }

    public function getShortReference($packageName) {
        $ref = $this->upstream::getReference($packageName);

        if (strlen($ref) > self::SHORT_COMMIT_LENGTH) {
            $ref = substr($ref, 0, self::SHORT_COMMIT_LENGTH);
        }

        return $ref;
    }

    public function getPhpVersion() {
        return PHP_VERSION;
    }

    public function getPhpExtVersion($extensionName) {
        return phpversion($name);
    }
}
