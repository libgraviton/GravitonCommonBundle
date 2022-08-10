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
        /*
        $objName = get_class($event->getObject());

        if (isset($this->loadSpans[$objName])) {
            return;
        }


        $this->tracer->

        $this->loadSpans[$objName] = GlobalTracer::get()->startActiveSpan('mongodb.load', ['tags' => ['doc' => $objName]]);
        */
    }

    public function postLoad(LifecycleEventArgs $event)
    {
        $this->tracer->finishActiveSpan();
        /*
        $objName = get_class($event->getObject());

        if (!isset($this->loadSpans[$objName])) {
            return;
        }

        $this->loadSpans[$objName]->close();
        */
    }

    public function prePersist(LifecycleEventArgs $event)
    {
        $objName = get_class($event->getObject());
        $this->tracer->startActiveSpan('doctrine.prepersist: '.$objName);
        /*
        $objName = get_class($event->getObject());
        if (isset($this->persistSpans[$objName])) {
            return;
        }

        $this->persistSpans[$objName] = GlobalTracer::get()->startActiveSpan('mongodb.persist', ['tags' => ['doc' => $objName]]);
        */
    }

    public function postPersist(LifecycleEventArgs $event)
    {
        $this->tracer->finishActiveSpan();
        /*
        $objName = get_class($event->getObject());

        if (!isset($this->persistSpans[$objName])) {
            return;
        }

        $this->persistSpans[$objName]->close();
        */
    }
}
