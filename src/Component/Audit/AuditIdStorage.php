<?php
/**
 * AuditIdStorage
 */
namespace Graviton\CommonBundle\Component\Audit;

use MongoDB\BSON\ObjectId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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

    public const ENVOY_HEADER_NAME = 'x-request-id';
    private RequestStack $requestStack;
    private ObjectId $auditId;
    private ?string $downStreamAuditId = null;

    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
        $this->auditId = new ObjectId();
    }

    public function get(): string {
        return $this->getString();
    }

    public function getString(): string {
        if (
            is_null($this->downStreamAuditId) &&
            ($this->requestStack->getMainRequest() instanceof Request && $this->requestStack->getMainRequest()->headers->has(self::ENVOY_HEADER_NAME))
        ) {
            $this->downStreamAuditId = $this->requestStack->getMainRequest()->headers->get(self::ENVOY_HEADER_NAME);
        }

        if (is_null($this->downStreamAuditId)) {
            return (string) $this->auditId;
        }

        return $this->downStreamAuditId;
    }
}
