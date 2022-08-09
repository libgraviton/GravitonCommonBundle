<?php
/**
 * GlobalTracer
 */

namespace Graviton\CommonBundle\Component\Tracing;

use GatewayBundle\Foundation\PsrResponse;
use Jaeger\Config;
use OpenTracing\Scope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class TracingListener implements EventSubscriberInterface
{

    private ?Scope $requestSpan = null;

    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest', 9999999]
            ],
            KernelEvents::FINISH_REQUEST => [
                ['onFinishRequest', -9999999]
            ],
            KernelEvents::TERMINATE => [
                ['onTerminate', -9999]
            ],
        ];
    }

    public function onRequest(RequestEvent $event)
    {
        GlobalTracer::init();
        $this->requestSpan = GlobalTracer::get()->startActiveSpan('request');
    }

    public function onFinishRequest(FinishRequestEvent $event)
    {
        if (is_null($this->requestSpan)) {
            return;
        }

        $this->requestSpan->close();
    }

    public function onTerminate(TerminateEvent $event)
    {
        GlobalTracer::get()->flush();
    }
}
