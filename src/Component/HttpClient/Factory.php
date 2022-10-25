<?php
/**
 * OptionalRedis
 */

namespace Graviton\CommonBundle\Component\HttpClient;

use Auxmoney\OpentracingBundle\Service\TracingService;
use Graviton\CommonBundle\Component\Audit\AuditIdStorage;
use Graviton\CommonBundle\Component\HttpClient\Guzzle\Middleware\Logging;
use Graviton\CommonBundle\Component\HttpClient\Guzzle\MiddlewareInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\UploadedFile;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Factory {

    private array $baseParams = [];
    private bool $debugLogging = false;
    private ?LoggerInterface $logger = null;
    private int $maxMessageLogLength = 5000;
    private ?TracingService $tracingService;
    private string $auditResponseHeaderName;
    private AuditIdStorage $auditIdStorage;

    public function __construct(
        $baseParams,
        $debugLogging,
        LoggerInterface $logger,
        $maxMessageLogLength,
        ?TracingService $tracingService,
        string $auditResponseHeaderName,
        AuditIdStorage $auditIdStorage
    ) {
        $this->baseParams = $baseParams;
        $this->debugLogging = $debugLogging;
        $this->logger = $logger;
        $this->maxMessageLogLength = $maxMessageLogLength;
        $this->tracingService = $tracingService;
        $this->auditResponseHeaderName = $auditResponseHeaderName;
        $this->auditIdStorage = $auditIdStorage;
    }

    /**
     * creates a new PsrHttpFactory based on diactoros
     *
     * @return PsrHttpFactory psr http factory
     */
    public function createPsrHttpFactory() {
        return new PsrHttpFactory(
            new ServerRequestFactory(),
            new StreamFactory(),
            new UploadedFileFactory(),
            new ResponseFactory()
        );
    }

    /**
     * creates a http client (ie guzzle)
     *
     * @return Client guzzle client
     */
    public function createHttpClient($addedParams = [], ?MiddlewareInterface $requestMiddleware = null) {
        $params = array_merge(
            $this->baseParams,
            $addedParams
        );

        if (!isset($params['handler'])) {
            $params['handler'] = HandlerStack::create();
        }

        $params['handler']->push($this->handlerCorrectQueryStringEncoding(), 'query_string_encoding');

        if ($requestMiddleware instanceof MiddlewareInterface) {
            $params['handler']->push(
                Middleware::mapRequest(
                    function (RequestInterface $request) use ($requestMiddleware) {
                        return $requestMiddleware->onRequest($request);
                    }
                ),
                'factory_init_req'
            );
            $params['handler']->push(
                Middleware::mapResponse(
                    function (ResponseInterface $response) use ($requestMiddleware) {
                        return $requestMiddleware->onResponse($response);
                    }
                ),
                'factory_init_resp'
            );
        }

        // tracing stuff
        $params['handler']->push(
            Middleware::mapRequest(
                function (RequestInterface $request) {
                    $request = $request->withHeader(
                        $this->auditResponseHeaderName,
                        $this->auditIdStorage->getString()
                    );
                    if (!is_null($this->tracingService)) {
                        $request = $this->tracingService->injectTracingHeaders($request);
                    }
                    return $request;
                }
            ),
            'tracing_and_audit'
        );

        // multipart fixes
        $params['handler']->push($this->handleMultipartRequest(), 'multipart_request');

        // attach our debug logger?
        if ($this->debugLogging && $this->logger instanceof LoggerInterface) {
            $params['handler']->push(
                Middleware::mapRequest(
                    Logging::getCallable($this->logger, 'REQUEST', $this->maxMessageLogLength)
                ),
                'factory_logging_req'
            );
            $params['handler']->push(
                Middleware::mapResponse(
                    Logging::getCallable($this->logger, 'RESPONSE', $this->maxMessageLogLength)
                ),
                'factory_logging_resp'
            );
        }

        return new Client($params);
    }

    /**
     * This corrects mistakes in the encoding of the query string..
     *
     * @return \Closure the middleware
     */
    private function handlerCorrectQueryStringEncoding()
    {
        return function (callable $nextHandler) {
            return function (RequestInterface $request, array $options) use ($nextHandler) {
                if ($request instanceof ServerRequestInterface) {
                    $serverParams = $request->getServerParams();
                    if (isset($serverParams['QUERY_STRING'])) {
                        $request = $request->withUri(
                            $request->getUri()->withQuery($serverParams['QUERY_STRING'])
                        );
                    }
                }
                return $nextHandler($request, $options);
            };
        };
    }

    private function handleMultipartRequest()
    {
        return function (callable $nextHandler) {
            return function (RequestInterface $request, array $options) use ($nextHandler) {
                $parsedBody = [];
                if (is_callable([$request, 'getParsedBody'])) {
                    $parsedBody = $request->getParsedBody();
                }

                $uploadedFiles = [];
                if (is_callable([$request, 'getUploadedFiles'])) {
                    $uploadedFiles = $request->getUploadedFiles();
                }

                $multiparts = [];
                if (is_array($parsedBody) && !empty($parsedBody)) {
                    foreach ($parsedBody as $name => $content) {
                        $multiparts[] = [
                            'name' => $name,
                            'contents' => $content
                        ];
                    }
                }

                if (is_array($uploadedFiles) && !empty($uploadedFiles)) {
                    foreach ($uploadedFiles as $name => $files) {
                        if (!is_array($files)) {
                            $files = [$files];
                        }

                        foreach ($files as $file) {
                            /** @var UploadedFile $file */
                            $multiparts[] = [
                                'name' => $name,
                                'filename' => $file->getClientFilename(),
                                'contents' => $file->getStream(),
                                'headers' => ['Content-Type' => $file->getClientMediaType()]
                            ];
                        }
                    }
                }

                if (!empty($multiparts)) {
                    $multipartStream = new MultipartStream($multiparts);

                    $request = $request
                        ->withUploadedFiles([])
                        ->withHeader('content-type', 'multipart/form-data; boundary="'.$multipartStream->getBoundary().'"')
                        ->withBody($multipartStream)
                        ->withHeader('content-length', $multipartStream->getSize());
                }

                return $nextHandler($request, $options);
            };
        };
    }
}
