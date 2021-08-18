<?php
/**
 * Factory
 */

namespace Graviton\CommonBundle\Component\HttpClient;

use Graviton\CommonBundle\Component\HttpClient\Guzzle\Middleware\Logging;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Monolog\Logger;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
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

    public function __construct(
        $baseParams,
        $debugLogging,
        LoggerInterface $logger,
        $maxMessageLogLength
    ) {
        $this->baseParams = $baseParams;
        $this->debugLogging = $debugLogging;
        $this->logger = $logger;
        $this->maxMessageLogLength = $maxMessageLogLength;
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
    public function createHttpClient($addedParams = []) {
        $params = array_merge(
            $this->baseParams,
            $addedParams
        );

        if (!isset($params['handler'])) {
            $params['handler'] = HandlerStack::create();
        }

        // attach our debug logger?
        if ($this->debugLogging && $this->logger instanceof LoggerInterface) {
            $params['handler']->push(
                Middleware::mapRequest(
                    Logging::getCallable($this->logger, 'REQUEST', $this->maxMessageLogLength)
                )
            );
            $params['handler']->push(
                Middleware::mapResponse(
                    Logging::getCallable($this->logger, 'RESPONSE', $this->maxMessageLogLength)
                )
            );
        }

        return new Client($params);
    }
}
