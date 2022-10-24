<?php
/**
 * RequestTimeSubscriber
 */
namespace Graviton\CommonBundle\Component\Logging\Listener;

use Graviton\CommonBundle\Component\Http\Foundation\PsrResponse;
use Graviton\CommonBundle\Component\Tracing\Stopwatch;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @package GatewaySecurityBundle\Listener
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class RequestTimeSubscriber implements EventSubscriberInterface
{

    public const REQUEST_TIME_MS = 'request_time_ms';
    public const REQUEST_TIME_GATEWAY_MS = 'request_time_gateway_ms';

    private LoggerInterface $logger;
    private string $appName;
    private ?Stopwatch $stopWatch;
    private ?PsrResponse $psrResponse;

    public function __construct(Logger $logger, string $appName, Stopwatch $stopwatch)
    {
        $this->logger = $logger;
        $this->appName = $appName;
        $this->stopWatch = $stopwatch;
    }

    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest', 9999999]
            ],
            KernelEvents::RESPONSE => [
                ['onResponse', -9999999]
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
        $this->stopWatch->start('request');
    }

    public function onResponse(ResponseEvent $event)
    {
        if ($event->getResponse() instanceof PsrResponse) {
            $this->psrResponse = $event->getResponse();
        } else {
            $this->psrResponse = null;
        }
    }

    public function onFinishRequest(FinishRequestEvent $event)
    {
        $this->stopWatch->stop('request');
    }

    public function onTerminate(TerminateEvent $event)
    {
        $requestDuration = $this->stopWatch->getEvent('request');

        // should it be ignored by response header?
        if (is_null($requestDuration) || $event->getResponse()->headers->has('x-no-log-request')) {
            $this->logger->info('no timing information');
            return;
        }

        $wholeRequestDuration = $requestDuration->getDuration();

        $psrRequestDuration = floatval(0);
        $upstreamName = 'internal';
        if ($this->psrResponse instanceof PsrResponse) {
            $upstreamName = $this->psrResponse->getUpstreamName();
            $status = $this->psrResponse->getStatusCode();
        } else {
            $status = $event->getResponse()->getStatusCode();
        }

        $event->getRequest()->attributes->set(self::REQUEST_TIME_MS, $wholeRequestDuration);

        $baseMetrics = [
            // these must stay in the same order to not break mtail parsing for gateway!
            'upstream_name' => $upstreamName,
            'status' => $status,
            'method' => $event->getRequest()->getMethod(),
            'whole_request_ms' => $wholeRequestDuration
        ];

        if ($this->appName == 'gateway') {
            // proxy duration?
            $proxyDuration = $this->stopWatch->getEvent('proxy')->getDuration();

            $gatewayOverhead = $wholeRequestDuration - $proxyDuration;
            $event->getRequest()->attributes->set(self::REQUEST_TIME_GATEWAY_MS, $gatewayOverhead);

            $baseMetrics['proxy_time_spent_ms'] = $proxyDuration;
            $baseMetrics['gateway_overhead_ms'] = $gatewayOverhead;
        }

        $baseMetrics['stopwatch'] = (string) $this->stopWatch;

        $this->logger->info(
            'RequestTime metrics',
            $baseMetrics
        );

        return $event;
    }
}
