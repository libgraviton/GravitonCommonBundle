<?php
/**
 * RequestTimeSubscriber
 */
namespace Graviton\CommonBundle\Component\Logging\Listener;

use Graviton\CommonBundle\Component\Foundation\PsrResponse;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;

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
    private ?float $wholeRequestDuration;
    private ?PsrResponse $psrResponse;

    public function __construct(Logger $logger, string $appName)
    {
        $this->logger = $logger;
        $this->appName = $appName;
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
        $this->stopWatch = new Stopwatch(true);
        $this->stopWatch->start('request');
        $this->wholeRequestDuration = null;
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
        if (!is_null($this->stopWatch)) {
            $this->wholeRequestDuration = $this->stopWatch->stop('request')->getDuration();
        }
    }

    public function onTerminate(TerminateEvent $event)
    {
        if (is_null($this->wholeRequestDuration)) {
            $this->logger->info('no timing information');
            return;
        }

        // should it be ignored by response header?
        if ($event->getResponse()->headers->has('x-no-log-request')) {
            return;
        }

        $psrRequestDuration = floatval(0);
        $upstreamName = 'internal';
        if ($this->psrResponse instanceof PsrResponse) {
            $psrRequestDuration = $this->psrResponse->getDuration();
            $upstreamName = $this->psrResponse->getUpstreamName();
            $status = $this->psrResponse->getStatusCode();
        } else {
            $status = $event->getResponse()->getStatusCode();
        }

        $event->getRequest()->attributes->set(self::REQUEST_TIME_MS, $this->wholeRequestDuration);

        $baseMetrics = [
            // these must stay in the same order to not break mtail parsing for gateway!
            'upstream_name' => $upstreamName,
            'status' => $status,
            'method' => $event->getRequest()->getMethod(),
            'whole_request_ms' => $this->wholeRequestDuration
        ];

        if ($this->appName == 'gateway') {
            $gatewayOverhead = $this->wholeRequestDuration - $psrRequestDuration;
            $event->getRequest()->attributes->set(self::REQUEST_TIME_GATEWAY_MS, $gatewayOverhead);

            $baseMetrics['proxy_time_spent_ms'] = $psrRequestDuration;
            $baseMetrics['gateway_overhead_ms'] = $gatewayOverhead;
        }

        $this->logger->info(
            'RequestTime metrics',
            $baseMetrics
        );

        return $event;
    }
}
