<?php
/**
 * AuditResponseListener
 */
namespace Graviton\CommonBundle\Component\Audit\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Graviton\CommonBundle\CommonUtils;
use Graviton\CommonBundle\Component\Audit\AuditIdStorage;
use Graviton\CommonBundle\Document\SecurityUserAudit;
use Graviton\CommonBundle\Component\Http\Foundation\PsrResponse;
use Graviton\CommonBundle\Component\Logging\Listener\RequestTimeSubscriber;
use Graviton\CommonBundle\Component\Redis\OptionalRedis;
use GuzzleHttp\Client;
use Psr\Http\Message\MessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class AuditResponseListener
 *
 * @package GatewaySecurityBundle\Listener
 * @author  List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://swisscom.ch
 */
class AuditResponseListener
{

    const ACTIVE_USER_TRACKING_EXPIRY = 3600;

    private LoggerInterface $logger;
    private bool $isEnabled;
    private bool $mongodbFallback;
    private bool $userTrackingEnabled;
    private string $userTrackingPrefix;
    private string $appName;
    private string $responseHeaderName;
    private bool $recordPayload;
    private ?string $skipOnHeaderPresence;
    private ?string $auditLoggerUrl;
    private ?string $auditDatabase;
    private ?string $auditCollection;
    private array $ignoredMethods;
    private ?string $ignoredPaths;
	private array $recordPayloadExceptions;
    private DocumentManager $documentManager;
    private TokenStorageInterface $tokenStorage;
    private AuditIdStorage $auditIdStorage;
    private OptionalRedis $optionalRedis;
    private Client $auditLoggerClient;

    /**
     * AuditResponseListener constructor.
     */
    public function __construct(
        LoggerInterface $logger,
        bool $isEnabled,
        bool $mongodbFallback,
        bool $userTrackingEnabled,
        string $appName,
        string $responseHeaderName,
        ?string $skipOnHeaderPresence,
        ?string $auditLoggerUrl,
        ?string $auditDatabase,
        ?string $auditCollection,
        bool $recordPayload,
        array $recordPayloadExceptions,
        array $ignoredMethods,
        ?string $ignoredPaths,
        DocumentManager $documentManager,
        TokenStorageInterface $tokenStorage,
        AuditIdStorage $auditIdStorage,
        OptionalRedis $optionalRedis,
        Client $auditLoggerClient
    ) {
        $this->logger = $logger;
        $this->isEnabled = $isEnabled;
        $this->mongodbFallback = $mongodbFallback;
        $this->userTrackingEnabled = $userTrackingEnabled;
        $this->userTrackingPrefix = 'usertracking:'.$appName.':';
        $this->appName = $appName;
        $this->responseHeaderName = $responseHeaderName;
        $this->skipOnHeaderPresence = $skipOnHeaderPresence;
        $this->auditLoggerUrl = $auditLoggerUrl;
        $this->auditDatabase = $auditDatabase;
        $this->auditCollection = $auditCollection;
        $this->recordPayload = $recordPayload;
        $this->recordPayloadExceptions = $recordPayloadExceptions;
        $this->ignoredMethods = $ignoredMethods;
        $this->ignoredPaths = $ignoredPaths;
        $this->documentManager = $documentManager;
        $this->tokenStorage = $tokenStorage;
        $this->auditIdStorage = $auditIdStorage;
        $this->optionalRedis = $optionalRedis;
        $this->auditLoggerClient = $auditLoggerClient;
    }

    /**
     * set basic information
     *
     * @param RequestEvent $event event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event)
	{
        $this->logger->info(
            'AUDIT LISTENER INIT',
            [
                'auditLogEnabled' => $this->isEnabled,
                'method' => $event->getRequest()->getMethod(),
                'path' => $event->getRequest()->getPathInfo()
            ]
        );
	}

    /**
     * set headers on response
     *
     * @param ResponseEvent $event event
     *
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        $shouldAddHeader = !in_array(strtoupper($event->getRequest()->getMethod()), $this->ignoredMethods);

        if (!$shouldAddHeader) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof PsrResponse) {
            $psrResponse = $response->getPsrResponse()->withHeader(
                $this->responseHeaderName,
                $this->auditIdStorage->getString()
            );

            $event->getResponse()->setPsrResponse($psrResponse);
        } else {
            $event->getResponse()->headers->set(
                $this->responseHeaderName,
                $this->auditIdStorage->getString()
            );
        }
    }

    /**
     * log the request to database if activated
     *
     * @param TerminateEvent $event event
     *
     * @return void
     */
    public function onKernelTerminate(TerminateEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // log for prometheus metrics (mtail)
        if ($response instanceof PsrResponse) {
            $responseType = 'PSR Response';
            $statusCode = $response->getPsrResponse()->getStatusCode();
        } else {
            $responseType = 'Symfony Response';
            $statusCode = $response->getStatusCode();
        }

        // token
        $token = $this->tokenStorage->getToken();
        $activeUserCount = 0;
        $username = '?';
        if (!is_null($token)) {
            $username = strtolower($token->getUserIdentifier());
        }

        // track "active" users. can only do if redis is available
        if ($this->userTrackingEnabled && !is_null($token) && $this->optionalRedis->isAvailable()) {
            $redis = $this->optionalRedis->getInstance();

            $key = $this->userTrackingPrefix.$username;
            $redis->set($key, 1);
            $redis->expire($key, self::ACTIVE_USER_TRACKING_EXPIRY);

            $iterator = null;
            $allKeys = [];
            // count all keys.. this is the recommended way as opposed to KEYS/SMEMBERS
            while (false !== ($keys = $redis->scan($iterator, $this->userTrackingPrefix.'*'))) {
                $allKeys = array_merge($allKeys, $keys);
            }

            $activeUserCount = count(array_unique($allKeys));
        }

        $this->logger->info(
            'Request ended with '.$responseType.' to client',
            [
                'status' => $statusCode,
                'activeUserCount' => $activeUserCount,
                'username' => $username
            ]
        );

        /**** actual audit log ****/

        // will not record anonymous stuff..
        if (is_null($token) || $token->getUserIdentifier() == 'anon.') {
            return;
        }

        if (!$this->shouldDoAuditLog($request)) {
            $this->logger->info('do no audit log');
            return;
        }

        $audit = new SecurityUserAudit();
        $audit->setId($this->auditIdStorage->get());
        $audit->setApp($this->appName);
        $audit->setCreatedAt(new \DateTime());
        $audit->setRequestUri($request->getRequestUri());
        $audit->setResponseCode($response->getStatusCode());
        $audit->setMethod($request->getMethod());
        $audit->setUsername(strtoupper($token->getUserIdentifier()));

        // payload?
		if ($this->recordPayload && !CommonUtils::subjectMatchesStringWildcards($this->recordPayloadExceptions, $request->getRequestUri(), $request->getMethod())) {
			$audit->setRequestBody($request->getContent());
		}

		// response?
        if ($this->shouldRecordResponseBody($response->getStatusCode()) && $response instanceof MessageInterface) {
            $audit->setResponseBody((string) $response->getBody());
        }

		if ($event->getRequest()->attributes->has(RequestTimeSubscriber::REQUEST_TIME_MS)) {
		    $audit->setRequestTimeMs($event->getRequest()->attributes->get(RequestTimeSubscriber::REQUEST_TIME_MS));
        }
        if ($event->getRequest()->attributes->has(RequestTimeSubscriber::REQUEST_TIME_GATEWAY_MS)) {
            $audit->setRequestTimeGatewayMs($event->getRequest()->attributes->get(RequestTimeSubscriber::REQUEST_TIME_GATEWAY_MS));
        }

        try {
            if (is_null($this->auditLoggerUrl)) {
                $collection = $this->documentManager->getClient()->selectDatabase($this->auditDatabase)->selectCollection($this->auditCollection);
                $collection->insertOne($audit);
                $this->logger->info(
                    'Inserted audit log event into database.',
                    [
                        'id' => $this->auditIdStorage->getString(),
                        'username' => $audit->getUsername()
                    ]
                );
            } else {
                // submit to our auditlogger!
                $this->auditLoggerClient->post(
                    $this->auditLoggerUrl,
                    [
                        'json' => [$audit]
                    ]
                );

                $this->logger->info(
                    'Sent audit log event to auditlogger.',
                    [
                        'id' => $this->auditIdStorage->getString(),
                        'username' => $audit->getUsername(),
                        'url' => $this->auditLoggerUrl
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->critical("Error persisting audit log!", ['exception' => $e]);
        }
    }

    private function shouldDoAuditLog(Request $request) : bool {
        // not enabled or an ignore method?
        if ($this->isEnabled !== true || in_array(strtoupper($request->getMethod()), $this->ignoredMethods)) {
            return false;
        }

        // "skip" header present? -> used to know if logged already by downstream
        if (!is_null($this->skipOnHeaderPresence) && $request->headers->has($this->skipOnHeaderPresence)) {
            return false;
        }

        // no auditlogger configured and no mongodb fallback
        if (is_null($this->auditLoggerUrl) && $this->mongodbFallback == false) {
            return false;
        }

        // paths ignore..
        if (!is_null($this->ignoredPaths) && CommonUtils::subjectMatchesStringWildcards($this->ignoredPaths, $request->getRequestUri())) {
            return false;
        }

        return true;
    }

    private function shouldRecordResponseBody($statusCode) {
        return str_starts_with((string) $statusCode, '4') || str_starts_with((string) $statusCode, '5');
    }
}
