<?php
/**
 * GlobalTracer
 */

namespace Graviton\CommonBundle\Component\Tracing;

use Doctrine\Bundle\MongoDBBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Events;
use GatewayBundle\Foundation\PsrResponse;
use Jaeger\Config;
use OpenTracing\Scope;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class DoctrineODMTracingListener implements EventSubscriberInterface
{

    private ?Scope $loadSpan = null;
    private ?Scope $persistSpan = null;

    public function getSubscribedEvents() : array
    {
        return [
            Events::preLoad,
            Events::postLoad,
            Events::prePersist,
            Events::postPersist
        ];
    }

    public function preLoad(PreLoadEventArgs $event)
    {
        $this->loadSpan = GlobalTracer::get()->startActiveSpan('mongodb.load', ['tags' => ['doc' => get_class($event->getObject())]]);
    }

    public function postLoad(LifecycleEventArgs $event)
    {
        if (!is_null($this->loadSpan)) {
            $this->loadSpan->close();
        }
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $this->persistSpan = GlobalTracer::get()->startActiveSpan('mongodb.persist', ['tags' => ['doc' => get_class($event->getObject())]]);
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        if (!is_null($this->persistSpan)) {
            $this->persistSpan->close();
        }
    }

}
