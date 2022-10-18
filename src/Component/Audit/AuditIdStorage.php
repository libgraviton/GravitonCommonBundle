<?php
/**
 * AuditIdStorage
 */
namespace Graviton\CommonBundle\Component\Audit;

use MongoDB\BSON\ObjectId;

/**
 * Class AuditIdStorage
 *
 * @package GatewaySecurityBundle\Security
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class AuditIdStorage
{
    /**
     * @var ObjectId
     */
    private ObjectId $auditId;

    public function __construct() {
        $this->auditId = new ObjectId();
    }

    public function get(): ObjectId {
        return $this->auditId;
    }

    public function getString(): string {
        return (string) $this->auditId;
    }
}
