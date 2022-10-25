<?php
/**
 * GlobalTracer
 */

namespace Graviton\CommonBundle\Component\Tracing;

use Auxmoney\OpentracingBundle\Service\TracingService;
use Doctrine\Bundle\MongoDBBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Graviton\CommonBundle\Component\Http\Foundation\PsrResponse;
use Jaeger\Config;
use OpenTracing\Scope;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class DoctrineODMTracingListener implements EventSubscriberInterface
{

    private TracingService $tracer;

    public function __construct(TracingService $tracer)
    {
        $this->tracer = $tracer;
    }

    private array $loadSpans = [];
    private array $persistSpans = [];

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
        $objName = get_class($event->getObject());
        $this->tracer->startActiveSpan('doctrine.load: '.$objName);
    }

    public function postLoad(LifecycleEventArgs $event)
    {
        $this->tracer->finishActiveSpan();
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $objName = get_class($event->getObject());
        $this->tracer->startActiveSpan('doctrine.prepersist: '.$objName);
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $this->tracer->finishActiveSpan();
    }
}
