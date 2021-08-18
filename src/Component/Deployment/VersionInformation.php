<?php
/**
 * Utility for version information
 */

namespace Graviton\CommonBundle\Component\Deployment;

use Composer\InstalledVersions;
use Doctrine\ODM\MongoDB\DocumentManager;

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
    public function __construct(?InstalledVersions $upstream = null)
    {
        $this->upstream = $upstream;
        if (is_null($this->upstream)) {
            $this->upstream = new InstalledVersions();
        }
    }

    public function getPrettyVersion($packageName) : ?string {
        $ver = (string) $this->upstream::getPrettyVersion($packageName);

        if (str_starts_with($ver, 'dev-')) {
            $ver .= '@'.$this->getShortReference($packageName);
        }

        return $ver;
    }

    public function getShortReference($packageName) : ?string {
        $ref = (string) $this->upstream::getReference($packageName);

        if (strlen($ref) > self::SHORT_COMMIT_LENGTH) {
            $ref = substr($ref, 0, self::SHORT_COMMIT_LENGTH);
        }

        return $ref;
    }

    public function getPhpVersion() {
        return PHP_VERSION;
    }

    public function getPhpExtVersion($extensionName) {
        return phpversion($extensionName);
    }
}
