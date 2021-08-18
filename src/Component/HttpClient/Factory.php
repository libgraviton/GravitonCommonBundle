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

    /**
     * creates a new PsrHttpFactory based on diactoros
     *
     * @return PsrHttpFactory psr http factory
     */
    public static function createPsrHttpFactory() {
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
    public static function createHttpClient(
        $baseParams = [],
        $debugLogging = false,
        LoggerInterface $logger = null,
        $maxMessageLogLength = 5000
    ) {

        // attach our debug logger?
        if ($debugLogging && $logger instanceof LoggerInterface) {
            if (!isset($baseParams['handler']) || !($baseParams['handler'] instanceof HandlerStack)) {
                $baseParams['handler'] = HandlerStack::create();
            }

            $baseParams['handler']->push(
                Middleware::mapRequest(
                    Logging::getCallable($logger, 'REQUEST', $maxMessageLogLength)
                )
            );
            $baseParams['handler']->push(
                Middleware::mapResponse(
                    Logging::getCallable($logger, 'RESPONSE', $maxMessageLogLength)
                )
            );
        }

        return new Client($baseParams);
    }
}
