<?php
/**
 * Entity representing an deployment coupled to the current commit hash
 */

namespace Graviton\CommonBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(collection="_DeploymentInformation")
 *
 * @author  List of contributors <https://github.com/libgraviton/DeploymentServiceBundle/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 */
class Deployment
{

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string") @MongoDB\Index
     */
    protected $packageName;

    /**
     * @MongoDB\Field(type="string") @MongoDB\Index
     */
    protected $commitHash;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $createdAt;

    /**
     * @return mixed
     */
    public function getCommitHash()
    {
        return $this->commitHash;
    }

    /**
     * @return mixed
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @param mixed $packageName
     */
    public function setPackageName($packageName): void
    {
        $this->packageName = $packageName;
    }

    /**
     * @param mixed $commitHash
     */
    public function setCommitHash($commitHash): void
    {
        $this->commitHash = $commitHash;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

}
